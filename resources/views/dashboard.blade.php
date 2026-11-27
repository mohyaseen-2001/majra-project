<x-app-layout>
    <div class="ops-heading">
        <div><span class="eyebrow"><span class="live-dot"></span> مركز المتابعة</span><h1>أهلاً، {{ auth()->user()->name }}</h1><p>تابع مخاطر السيول وإغلاقات الطرق، أو أرسل بلاغاً جديداً.</p></div>
    </div>
    <div class="ops-stats">
        <div class="ops-stat"><span>نوع الحساب</span><strong>مواطن</strong></div>
        <div class="ops-stat"><span>حالة الحساب</span><strong><i>●</i> مفعّل</strong></div>
        <div class="ops-stat"><span>المنطقة الزمنية</span><strong>عمّان · الأردن</strong></div>
    </div>
    <div class="company-shortcuts">
        <a class="citizen-card" href="{{ route('map') }}"><h2>خريطة المخاطر</h2><p>اطّلع على البلاغات والإغلاقات والتنبيهات.</p></a>
        <a class="citizen-card" href="{{ route('feed') }}"><h2>البلاغات</h2><p>راجع البلاغات التي تحقّق منها فريق مَجرى.</p></a>
        <a class="citizen-card" href="{{ route('reports.create') }}"><h2>أرسل بلاغاً</h2><p>شارِك وصف الخطر وموقعه وصورة من مكان آمن.</p></a>
    </div>
</x-app-layout>
