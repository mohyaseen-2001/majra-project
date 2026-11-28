<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SafetyAssistantController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:500']]);
        $topic = $this->topic($data['question']);

        if ($topic === 'urgent') {
            return response()->json(['answer' => 'إذا كان هناك خطر مباشر، ابتعد إلى مكان آمن واتصل برقم الطوارئ الموحد 911 فوراً. لا تدخل المياه ولا تحاول إنقاذ أحد بنفسك إذا كان ذلك يعرّضك للخطر.', 'source' => 'مديرية الأمن العام الأردنية', 'urgent' => true]);
        }
        if ($topic === 'emergency') {
            return response()->json(['answer' => 'رقم الطوارئ الموحد في الأردن 911. تذكر بوابة الأردن أيضاً الإسعاف 193 والإطفاء 199. عند خطر مباشر اتصل بـ911 واتبع تعليمات المستجيب.', 'source' => 'بوابة الأردن الرسمية', 'urgent' => true]);
        }
        if ($topic === 'map') {
            return response()->json(['answer' => 'تُستخدم ألوان الخريطة لتمييز مستوى الخطر المسجل في البلاغ. تحقق من حالة البلاغ ووقت تحديثه ومصدره؛ الألوان لا تغني عن تعليمات الجهات الرسمية.']);
        }
        if ($topic === 'report') {
            return response()->json(['answer' => 'لإرسال بلاغ، سجّل الدخول كمواطن، افتح «أرسل بلاغاً»، أضف صورة التقطتها من مكان آمن، وحدد الموقع والوصف. يُحال البلاغ للمراجعة عند تعذّر حسم فحص الصورة، ولا يُتتبع موقعك في الخلفية.']);
        }
        if ($topic === 'hospital') {
            return response()->json(['answer' => 'افتح الخريطة واستخدم خيار المستشفيات لعرض بيانات OpenStreetMap المتاحة. إذا كانت حالة طارئة فاتصل بـ911؛ قد لا تكون بيانات الخريطة كاملة أو محدثة.']);
        }
        if ($topic === 'flood') {
            return response()->json(['answer' => 'لا تعبر المياه الجارية أو الأنفاق المغمورة بالمركبة أو سيراً، وابتعد عن مجرى السيل. لا تقترب لتصوير الخطر، واتبع التنبيهات الرسمية المحلية.']);
        }

        return response()->json(['answer' => 'أستطيع المساعدة بمعلومات السلامة، البلاغات، ألوان الخريطة، أرقام الطوارئ والمستشفيات. لم أتمكن من تحديد الإجابة؛ راجع دليل السلامة أو اتصل بـ911 عند الخطر المباشر.', 'source' => 'دليل السلامة في مَجرى']);
    }

    private function topic(string $question): ?string
    {
        $rules = [
            'urgent' => '/خطر مباشر|في خطر|عالق|غريق|إصابة|مصاب|انجراف|محاصر|حادث|\b(?:trapped|drowning|immediate danger|injured|accident)\b/iu',
            'emergency' => '/رقم|أرقام|طوارئ|إسعاف|اسعاف|دفاع مدني|إطفاء|اطفاء|\b(?:emergency|911|ambulance|fire brigade)\b/iu',
            'map' => '/لون|ألوان|الوان|الخريطة|مستوى الخطر|\b(?:map colou?rs?|risk levels?|legend)\b/iu',
            'report' => '/بلاغ|أرسل|ارسل|صورة|موقع|\b(?:submit|file|send) (?:a |the )?report\b/iu',
            'hospital' => '/مستشفى|مستشفيات|قريب|\b(?:hospitals?|medical cent(?:er|re)s?)\b/iu',
            'flood' => '/مطر|سيل|سيول|مياه|نفق|طريق|\b(?:flood(?:ing|s)?|heavy rain|submerged roads?)\b/iu',
        ];

        foreach ($rules as $topic => $pattern) {
            if (preg_match($pattern, $question)) {
                return $topic;
            }
        }

        if (! $key = config('services.gemini.key')) {
            return null;
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $key, 'Api-Revision' => '2026-05-20'])
                ->withOptions(['version' => 1.1])
                ->connectTimeout(2)->timeout(5)
                ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                    'model' => config('services.gemini.model'),
                    'store' => false,
                    'input' => 'Classify the Majra safety question as exactly one of urgent, emergency, map, report, hospital, flood, or unknown. Never answer the question. Question: '.$question,
                ])->throw()->json();
            $text = collect($response['steps'] ?? [])->where('type', 'model_output')
                ->flatMap(fn ($step) => $step['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '';
            $topic = trim($text);

            return array_key_exists($topic, $rules) ? $topic : null;
        } catch (\Illuminate\Http\Client\HttpClientException) {
            return null;
        }
    }
}
