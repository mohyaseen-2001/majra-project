<x-guest-layout>
    <h1 class="auth-title">تأكيد البريد الإلكتروني</h1>
    <p class="auth-subtitle">شكراً لتسجيلك. إذا كان تأكيد البريد مفعّلاً، افتح رسالة التحقق المرسلة إلى بريدك.</p>
    @if (session('status') == 'verification-link-sent')
        <div class="auth-session-status">أرسلنا رابط تحقق جديداً إلى بريدك الإلكتروني.</div>
    @endif
    <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="button auth-submit" type="submit">إعادة إرسال رابط التحقق</button></form>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="auth-footnote auth-link-button" type="submit">تسجيل الخروج</button></form>
</x-guest-layout>
