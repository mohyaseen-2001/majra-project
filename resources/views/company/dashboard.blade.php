<x-app-layout>
    <section class="citizen-page company-page">
        <div class="citizen-heading"><div><span class="eyebrow">غرفة متابعة المؤسسات</span><h1>أهلاً، {{ $company->name }}</h1><p>ملخص المخاطر والإغلاقات في مناطق العمل التي اخترتها.</p></div><a class="button" href="{{ route('company.areas') }}">إدارة المناطق</a></div>
        <div class="ops-stats"><div class="ops-stat"><span>مناطق المتابعة</span><strong>{{ $areaCount }}</strong></div><div class="ops-stat"><span>إغلاقات نشطة في مناطقك</span><strong>{{ $closureCount }}</strong></div><div class="ops-stat"><span>الباقة</span><strong>{{ $activeSubscription?->plan?->name ?? 'مجانية / غير مفعلة' }}</strong></div></div>
        @if(session('success') || session('error') || session('info'))<div class="company-notice">{{ session('success') ?? session('error') ?? session('info') }}</div>@endif
        <section class="ops-panel"><div class="citizen-heading"><div><h2>أحدث البلاغات في مناطقك</h2><p>تظهر البلاغات التي راجعها فريق مَجرى فقط.</p></div><a class="text-link" href="{{ route('company.reports') }}">كل التقارير ←</a></div>
            <div class="company-list">@forelse($reports as $report)<a href="{{ route('reports.show', $report->public_id) }}"><span class="risk-tag risk-{{ $report->risk_level }}">{{ $report->risk_level }}</span><strong>{{ $report->title }}</strong><span>{{ $report->area_name }}</span><time>{{ $report->created_at->diffForHumans() }}</time></a>@empty<p>اختر مناطق متابعة من صفحة المناطق لعرض البلاغات المرتبطة بها.</p>@endforelse</div>
        </section>
        <div class="company-shortcuts"><a class="citizen-card" href="{{ route('company.reports') }}"><span class="eyebrow">الرصد</span><h2>البلاغات والإغلاقات</h2><p>فلترة حسب مستوى الخطر والتاريخ.</p></a><a class="citizen-card" href="{{ route('company.subscription') }}"><span class="eyebrow">الخدمات</span><h2>الباقات والفوترة</h2><p>إدارة الاشتراك التجريبي وسجل الدفعات.</p></a></div>
    </section>
</x-app-layout>
