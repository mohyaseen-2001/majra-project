<x-guest-layout>
    <h1 class="auth-title">أنشئ حساب مَجرى</h1>
    <p class="auth-subtitle">اختر نوع الحساب. صلاحية الإدارة تُمنح من النظام فقط.</p>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div><label for="name">الاسم الكامل</label><input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="الاسم الكامل"><x-input-error :messages="$errors->get('name')" class="field-error" /></div>
        <div><label for="email">البريد الإلكتروني</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@example.com"><x-input-error :messages="$errors->get('email')" class="field-error" /></div>
        <div><label for="role">نوع الحساب</label><select id="role" name="role" required><option value="citizen" @selected(old('role', 'citizen') === 'citizen')>مواطن — بلاغات ومعلومات عامة</option><option value="company" @selected(old('role') === 'company')>شركة — متابعة مناطق العمل</option></select><x-input-error :messages="$errors->get('role')" class="field-error" /></div>
        <div><label for="password">كلمة المرور</label><input id="password" type="password" name="password" required autocomplete="new-password" placeholder="8 أحرف على الأقل"><x-input-error :messages="$errors->get('password')" class="field-error" /></div>
        <div><label for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="أعد كتابة كلمة المرور"></div>
        <button class="button auth-submit" type="submit">إنشاء الحساب <span aria-hidden="true">←</span></button>
    </form>
    <p class="auth-footnote">عندك حساب؟ <a href="{{ route('login') }}">سجّل الدخول</a></p>
</x-guest-layout>
