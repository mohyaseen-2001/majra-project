<header class="site-header">
    <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><span></span><span></span><span></span></span><span>مَجرى<small>غرفة عمليات السيول</small></span></a>
    <nav class="site-nav">
        <a href="{{ route('about') }}">عن مَجرى</a>
        <a href="{{ route('safety') }}">دليل السلامة</a>
        <a href="{{ route('pricing') }}">للشركات</a>
        <a href="{{ route('contact') }}">تواصل معنا</a>
        @guest
            <a href="{{ route('register') }}">إنشاء حساب</a>
        @endguest
    </nav>
    @auth
        <a class="button button-small" href="{{ route('dashboard') }}">لوحة المتابعة</a>
    @else
        <a class="button button-small" href="{{ route('login') }}">تسجيل الدخول</a>
    @endauth
</header>
