<x-guest-layout>
    <h1 class="auth-title">استعادة كلمة المرور</h1>
    <p class="auth-subtitle">اكتب بريدك الإلكتروني وسنرسل إليه رابط إعادة التعيين إذا كانت خدمة البريد مفعّلة.</p>
    <x-auth-session-status class="auth-session-status" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div><label for="email">البريد الإلكتروني</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@example.com"><x-input-error :messages="$errors->get('email')" class="field-error" /></div>
        <button class="button auth-submit" type="submit">إرسال رابط الاستعادة <span aria-hidden="true">←</span></button>
    </form>
    <p class="auth-footnote"><a href="{{ route('login') }}">العودة لتسجيل الدخول</a></p>
</x-guest-layout>
