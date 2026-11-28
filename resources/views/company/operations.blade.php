<x-app-layout>
    <section class="citizen-page company-page">
        <div class="citizen-heading">
            <div>
                <span class="eyebrow">باقة العمليات</span>
                <h1>سجل الإغلاقات والملخص التشغيلي</h1>
                <p>راجع الإغلاقات السابقة والبلاغات المنشورة في مناطق متابعتك خلال الفترة المختارة.</p>
            </div>
            <a class="text-link" href="{{ route('company.reports') }}">الرجوع إلى التقارير</a>
        </div>

        <form class="citizen-card company-filters" method="GET" action="{{ route('company.operations') }}">
            <label>المنطقة
                <select name="area">
                    <option value="">كل مناطق المتابعة</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) $selectedArea === (string) $area->id)>{{ $area->area_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>من تاريخ<input type="date" name="from" required value="{{ old('from', $from) }}"></label>
            <label>إلى تاريخ<input type="date" name="to" required value="{{ old('to', $to) }}"></label>
            <button class="button button-small">تطبيق</button>
        </form>
        @if($errors->any())
            <p class="company-notice" role="alert">اختر تاريخ بداية ونهاية صالحين، بحيث لا يسبق تاريخ النهاية تاريخ البداية ولا يتجاوز اليوم.</p>
        @endif

        <h2 class="company-section-title">الملخص التشغيلي</h2>
        <div class="plan-grid">
            <article class="citizen-card"><h3>الإغلاقات السابقة</h3><strong>{{ $closures->total() }}</strong></article>
            <article class="citizen-card"><h3>البلاغات المنشورة</h3><strong>{{ $reportCount }}</strong></article>
            <article class="citizen-card"><h3>البلاغات عالية الخطورة أو الحرجة</h3><strong>{{ $highRiskCount }}</strong></article>
            @if($mostAffected)
                <article class="citizen-card"><h3>المنطقة الأكثر إغلاقًا</h3><strong>{{ $mostAffected->area_name }} ({{ $mostAffected->total }})</strong></article>
            @endif
        </div>

        <h2 class="company-section-title">سجل الإغلاقات</h2>
        <p>يتحدد نطاق السجل حسب تاريخ بدء الإغلاق.</p>
        <div class="company-list">
            @forelse($closures as $closure)
                <article>
                    <strong>{{ $closure->title }}</strong>
                    <span>{{ $closure->area_name }}</span>
                    <time>{{ ($closure->starts_at ?? $closure->created_at)->format('Y-m-d') }}</time>
                    @if($closure->diversion)<span>{{ $closure->diversion }}</span>@endif
                </article>
            @empty
                <div class="citizen-card empty-state">لا توجد إغلاقات سابقة ضمن الفترة والمناطق المختارة.</div>
            @endforelse
        </div>
        <div class="pagination-wrap">{{ $closures->links() }}</div>
    </section>
</x-app-layout>
