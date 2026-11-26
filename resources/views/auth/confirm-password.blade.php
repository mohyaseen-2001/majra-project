<x-guest-layout>
    <h1 class="auth-title">تأكيد كلمة المرور</h1>
    <p class="auth-subtitle">هذه صفحة آمنة. أكّد كلمة المرور للمتابعة.</p>
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div><label for="password">كلمة المرور</label><input id="password" type="password" name="password" required autofocus autocomplete="current-password"><x-input-error :messages="$errors->get('password')" class="field-error" /></div>
        <button class="button auth-submit" type="submit">تأكيد</button>
    </form>
</x-guest-layout>
