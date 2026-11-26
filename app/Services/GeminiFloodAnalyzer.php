<?php

namespace App\Services;

use App\Models\AiAnalysis;
use App\Models\Report;
use App\Models\ReportMedia;
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
                ->connectTimeout(5)->timeout(25)
                ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                    'model' => config('services.gemini.model', 'gemini-3.8-flash'),
                    'store' => false,
                    'input' => [
                        [
                            'type' => 'text',
                            'text' => 'افحص الصورة كمساعد لمراجع بشري في منصة سلامة عامة. لا تستنتج الموقع أو الوقت من الصورة. أعطِ توصية فقط ولا تعتمد البلاغ. أجب JSON وفق المخطط: هل تظهر مياه سيول أو تجمع مياه خطر؟ نوع المشهد ومستوى الخطر والثقة 0 إلى 1 وجودة وضوح الصورة 0 إلى 100 وملاحظة موجزة بالعربية. إذا لم تستطع الجزم فاختر false أو unknown بثقة منخفضة.',
                        ],
                        ['type' => 'image', 'data' => base64_encode($bytes), 'mime_type' => $mime],
                    ],
                    'response_format' => [
                        'type' => 'text',
                        'mime_type' => 'application/json',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'is_flood' => ['type' => 'boolean'],
                                'hazard_type' => ['type' => 'string', 'enum' => ['flood', 'waterlogging', 'road_hazard', 'other', 'unknown']],
                                'risk_level' => ['type' => 'string', 'enum' => ['unknown', 'low', 'medium', 'high', 'critical']],
                                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                                'image_quality_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                                'notes' => ['type' => 'string'],
                            ],
                            'required' => ['is_flood', 'hazard_type', 'risk_level', 'confidence', 'image_quality_score', 'notes'],
                        ],
                    ],
                ])->throw()->json();

            $text = collect($response['steps'] ?? [])->where('type', 'model_output')->flatMap(fn ($step) => $step['content'] ?? [])->firstWhere('type', 'text')['text'] ?? null;
            $result = is_string($text) ? json_decode($text, true, flags: JSON_THROW_ON_ERROR) : null;
            if (! is_array($result)) {
                throw new \UnexpectedValueException('Gemini response did not include structured output.');
            }

            $analysis = $report->aiAnalyses()->create([
                'provider' => 'gemini', 'status' => 'checked', 'is_flood' => (bool) $result['is_flood'],
                'is_duplicate' => false, 'hazard_type' => $result['hazard_type'], 'risk_level' => $result['risk_level'],
                'confidence' => min(1, max(0, (float) $result['confidence'])),
                'observations' => ['image_quality_score' => min(100, max(0, (int) $result['image_quality_score']))],
                'admin_notes' => mb_substr((string) $result['notes'], 0, 2000), 'raw_response' => $text, 'checked_at' => now(),
            ]);
            $report->update(['status' => 'ai_checked', 'risk_level' => $result['risk_level'], 'ai_checked_at' => now()]);

            return $analysis;
        } catch (\Throwable) {
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
