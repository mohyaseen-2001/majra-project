<x-guest-layout>
    <h1 class="auth-title">أهلاً بعودتك</h1>
    <p class="auth-subtitle">سجّل دخولك لمتابعة مَجرى.</p>
    <x-auth-session-status class="auth-session-status" :status="session('status')" />
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div><label for="email">البريد الإلكتروني</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@example.com"><x-input-error :messages="$errors->get('email')" class="field-error" /></div>
        <div><label for="password">كلمة المرور</label><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"><x-input-error :messages="$errors->get('password')" class="field-error" /></div>
        <div class="remember-row"><label for="remember_me"><input id="remember_me" type="checkbox" name="remember"> تذكّرني على هذا الجهاز</label>
            @if (Route::has('password.request'))<a href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>@endif
        </div>
        <button class="button auth-submit" type="submit">تسجيل الدخول <span aria-hidden="true">←</span></button>
    </form>
    <p class="auth-footnote">جديد على مَجرى؟ <a href="{{ route('register') }}">أنشئ حساباً</a></p>
</x-guest-layout>
