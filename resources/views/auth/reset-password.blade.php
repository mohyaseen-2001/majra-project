<x-guest-layout>
    <h1 class="auth-title">تعيين كلمة مرور جديدة</h1>
    <p class="auth-subtitle">اختر كلمة مرور جديدة لحسابك.</p>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div><label for="email">البريد الإلكتروني</label><input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"><x-input-error :messages="$errors->get('email')" class="field-error" /></div>
        <div><label for="password">كلمة المرور الجديدة</label><input id="password" type="password" name="password" required autocomplete="new-password"><x-input-error :messages="$errors->get('password')" class="field-error" /></div>
        <div><label for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"><x-input-error :messages="$errors->get('password_confirmation')" class="field-error" /></div>
        <button class="button auth-submit" type="submit">حفظ كلمة المرور</button>
    </form>
</x-guest-layout>
