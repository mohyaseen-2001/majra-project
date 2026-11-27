<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SafetyAssistantTest extends TestCase
{
    public function test_english_report_question_works_without_gemini(): void
    {
        Http::preventStrayRequests();

        $this->postJson('/api/safety-assistant', ['question' => 'How do I submit a report?'])
            ->assertOk()->assertJsonPath('answer', 'لإرسال بلاغ، سجّل الدخول كمواطن، افتح «أرسل بلاغاً»، أضف صورة التقطتها من مكان آمن، وحدد الموقع والوصف. يبقى البلاغ قيد المراجعة حتى يعتمده الفريق، ولا يُتتبع موقعك في الخلفية.');

        Http::assertNothingSent();
    }

    public function test_gemini_routes_an_unrecognized_question_to_the_reviewed_answer(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake(['https://generativelanguage.googleapis.com/*' => Http::response([
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'hospital']]]],
        ])]);

        $this->postJson('/api/safety-assistant', ['question' => 'Where can I find urgent care?'])
            ->assertOk()->assertJsonPath('answer', 'افتح الخريطة واستخدم خيار المستشفيات لعرض بيانات OpenStreetMap المتاحة. إذا كانت حالة طارئة فاتصل بـ911؛ قد لا تكون بيانات الخريطة كاملة أو محدثة.');
    }
    public function test_emergency_question_returns_official_emergency_guidance(): void
    {
        $this->postJson('/api/safety-assistant', ['question' => 'أنا عالق في المياه'])
            ->assertOk()
            ->assertJsonPath('urgent', true)
            ->assertJsonFragment(['answer' => 'إذا كان هناك خطر مباشر، ابتعد إلى مكان آمن واتصل برقم الطوارئ الموحد 911 فوراً. لا تدخل المياه ولا تحاول إنقاذ أحد بنفسك إذا كان ذلك يعرّضك للخطر.']);
    }

    public function test_map_color_question_does_not_trigger_emergency_answer(): void
    {
        $this->postJson('/api/safety-assistant', ['question' => 'ما معنى ألوان مستوى الخطر في الخريطة؟'])
            ->assertOk()
            ->assertJsonMissingPath('urgent');
    }

    public function test_emergency_numbers_question_returns_the_listed_numbers(): void
    {
        $this->postJson('/api/safety-assistant', ['question' => 'ما رقم الطوارئ؟'])
            ->assertOk()
            ->assertJsonFragment(['answer' => 'رقم الطوارئ الموحد في الأردن 911. تذكر بوابة الأردن أيضاً الإسعاف 193 والإطفاء 199. عند خطر مباشر اتصل بـ911 واتبع تعليمات المستجيب.']);
    }
}
