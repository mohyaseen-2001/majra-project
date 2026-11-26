<?php

namespace Tests\Feature;

use Tests\TestCase;

class SafetyAssistantTest extends TestCase
{
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
