<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicContactPageTest extends TestCase
{
    public function test_contact_page_renders_successfully(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('تواصل معنا')
            ->assertSee('إرسال الرسالة');
    }
}
