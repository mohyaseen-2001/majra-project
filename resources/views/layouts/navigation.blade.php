<nav class="ops-nav">
    <a class="brand" href="{{ route('home') }}"><span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span><span>مَجرى<small>غرفة عمليات السيول</small></span></a>
    <div class="ops-nav-links">
        @if(Auth::user()->hasRole('citizen'))
            <a href="{{ route('map') }}">الخريطة</a><a href="{{ route('feed') }}">البلاغات</a><a href="{{ route('reports.create') }}">أرسل بلاغاً</a>
        @else
            <a href="{{ Auth::user()->hasRole('admin') ? route('admin.dashboard') : route('company.dashboard') }}">لوحة المتابعة</a>
        @endif
        <a href="{{ route('profile.edit') }}">الملف الشخصي</a>
    </div>
    <div class="ops-user"><span>{{ Auth::user()->name }} <small>· {{ Auth::user()->role === 'company' ? 'شركة' : (Auth::user()->role === 'admin' ? 'إدارة' : 'مواطن') }}</small></span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="ops-logout" type="submit">تسجيل الخروج</button></form>
    </div>
</nav>
