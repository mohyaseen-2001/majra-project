<?php

namespace App\Services;

use App\Models\AiAnalysis;
use App\Models\Report;
use App\Models\ReportMedia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GeminiFloodAnalyzer
{
    public function analyze(Report $report): ?AiAnalysis
    {
        $report->loadMissing('media');
        $media = $report->media->first();
        if (! $media) {
            return $this->recordFailure($report, 'لا توجد صورة لفحصها؛ البلاغ محفوظ للمراجعة اليدوية.');
        }

        if ($media->sha256) {
            $duplicate = ReportMedia::query()->where('sha256', $media->sha256)
                ->where('report_id', '!=', $report->id)->with('report:id,public_id,title,status')->first();
            if ($duplicate) {
                $analysis = $report->aiAnalyses()->create([
                    'provider' => 'local-duplicate-check', 'status' => 'checked', 'is_duplicate' => true,
                    'duplicate_of_report_id' => $duplicate->report_id,
                    'admin_notes' => 'تطابق ملف الصورة مع صورة في بلاغ سابق؛ تحقّق من البلاغين يدوياً.',
                    'observations' => ['match' => 'sha256'], 'checked_at' => now(),
                ]);
                $report->update(['status' => 'ai_checked', 'ai_checked_at' => now()]);

                return $analysis;
            }
        }

        $apiKey = config('services.gemini.key');
        if (! $apiKey) {
            return $this->recordFailure($report, 'Gemini غير مفعّل. البلاغ بانتظار المراجعة اليدوية.');
        }

        try {
            $bytes = Storage::disk($media->disk)->get($media->path);
            $mime = $media->mime_type ?: 'image/jpeg';
            $response = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => $apiKey, 'Api-Revision' => '2026-05-20'])
                ->withOptions(['version' => 1.1])
                ->connectTimeout(4)->timeout(12)
                ->retry(2, 400, fn (\Exception $exception, PendingRequest $request) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()))
                ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                    'model' => config('services.gemini.model'),
                    'store' => false,
                    'input' => [
                        [
                            'type' => 'text',
                            'text' => 'افحص الصورة وحدها لبلاغ سيول. لا تستنتج الموقع أو التاريخ. صنّف visible_flood عندما تظهر مياه سيول أو تجمع مياه خطر بوضوح. صنّف no_visible_hazard فقط عندما تُظهر صورة واضحة طريقاً أو مشهداً ذا صلة بدون خطر ظاهر. صنّف uncertain إذا كانت الصورة ضبابية أو مقتطعة أو غير ذات صلة أو تحتمل أكثر من تفسير. أعد مستوى الخطر والثقة بجوابك وجودة الصورة وملاحظة عربية. تجنب المبالغة في الثقة.',
                        ],
                        ['type' => 'image', 'data' => base64_encode($bytes), 'mime_type' => $mime],
                    ],
                    'response_format' => [
                        'type' => 'text',
                        'mime_type' => 'application/json',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'scene_decision' => ['type' => 'string', 'enum' => ['visible_flood', 'no_visible_hazard', 'uncertain']],
                                'is_flood' => ['type' => 'boolean'],
                                'hazard_type' => ['type' => 'string', 'enum' => ['flood', 'waterlogging', 'road_hazard', 'other', 'unknown']],
                                'risk_level' => ['type' => 'string', 'enum' => ['unknown', 'low', 'medium', 'high', 'critical']],
                                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                                'image_quality_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                                'notes' => ['type' => 'string'],
                            ],
                            'required' => ['scene_decision', 'is_flood', 'hazard_type', 'risk_level', 'confidence', 'image_quality_score', 'notes'],
                        ],
                    ],
                ])->throw()->json();

            $text = collect($response['steps'] ?? [])->where('type', 'model_output')->flatMap(fn ($step) => $step['content'] ?? [])->firstWhere('type', 'text')['text'] ?? null;
            $result = is_string($text) ? json_decode($text, true, flags: JSON_THROW_ON_ERROR) : null;
            if (! is_array($result)
                || ! is_bool($result['is_flood'] ?? null)
                || ! in_array($result['scene_decision'] ?? null, ['visible_flood', 'no_visible_hazard', 'uncertain'], true)
                || ! in_array($result['hazard_type'] ?? null, ['flood', 'waterlogging', 'road_hazard', 'other', 'unknown'], true)
                || ! in_array($result['risk_level'] ?? null, ['unknown', 'low', 'medium', 'high', 'critical'], true)
                || ! is_numeric($result['confidence'] ?? null)
                || ! is_int($result['image_quality_score'] ?? null)
                || ! is_string($result['notes'] ?? null)) {
                throw new \UnexpectedValueException('Gemini response did not include structured output.');
            }

            $analysis = $report->aiAnalyses()->create([
                'provider' => 'gemini', 'status' => 'checked', 'is_flood' => (bool) $result['is_flood'],
                'is_duplicate' => false, 'hazard_type' => $result['hazard_type'], 'risk_level' => $result['risk_level'],
                'confidence' => min(1, max(0, (float) $result['confidence'])),
                'observations' => ['image_quality_score' => min(100, max(0, (int) $result['image_quality_score']))],
                'admin_notes' => mb_substr((string) $result['notes'], 0, 2000), 'raw_response' => $text, 'checked_at' => now(),
            ]);
            $clear = (float) $result['confidence'] >= 0.9 && $result['image_quality_score'] >= 70;
            $canPublish = $clear && $result['scene_decision'] === 'visible_flood' && $result['is_flood']
                && in_array($result['hazard_type'], ['flood', 'waterlogging'], true)
                && in_array($result['risk_level'], ['low', 'medium', 'high', 'critical'], true);
            $canDecline = $clear && $result['scene_decision'] === 'no_visible_hazard' && ! $result['is_flood']
                && $result['hazard_type'] === 'other' && in_array($result['risk_level'], ['low', 'unknown'], true);
            $status = $canPublish ? 'approved' : ($canDecline ? 'rejected' : 'ai_checked');

            $report->newQuery()->whereKey($report->id)->whereIn('status', ['pending', 'ai_checked'])
                ->where('expires_at', '>', now())
                ->update([
                    'status' => $status,
                    'hazard_type' => $result['hazard_type'],
                    'risk_level' => $result['risk_level'],
                    'ai_checked_at' => now(),
                ]);
            $report->refresh();

            return $analysis;
        } catch (\Throwable $exception) {
            logger()->warning('Gemini image analysis unavailable', [
                'report_id' => $report->id,
                'exception' => $exception::class,
                'http_status' => $exception instanceof RequestException ? $exception->response->status() : null,
            ]);
            return $this->recordFailure($report, 'تعذر فحص الصورة آلياً؛ البلاغ محفوظ للمراجعة اليدوية.');
        }
    }

    private function recordFailure(Report $report, string $message): AiAnalysis
    {
        return $report->aiAnalyses()->create([
            'provider' => 'gemini', 'status' => 'unavailable', 'admin_notes' => $message,
        ]);
    }
}
