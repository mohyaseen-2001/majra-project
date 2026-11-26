<x-app-layout>
    <section class="citizen-page">
        <div class="citizen-heading"><div><span class="eyebrow">مجتمع يشارك المعلومة</span><h1>موجز البلاغات</h1><p>البلاغات التي راجعها فريق مَجرى فقط تظهر هنا.</p></div><a class="button" href="{{ route('reports.create') }}">＋ أرسل بلاغاً</a></div>
        <div class="report-grid">
            @forelse($reports as $report)
                <article class="report-card">
                    @if($report->media->first())<a href="{{ route('reports.show', $report->public_id) }}" class="report-photo"><img src="{{ Storage::disk($report->media->first()->disk)->url($report->media->first()->path) }}" alt="صورة البلاغ" loading="lazy"></a>@else<div class="report-photo photo-placeholder">مَجرى</div>@endif
                    <div class="report-card-body"><div class="report-tags"><span class="risk-tag risk-{{ $report->risk_level }}">{{ $report->risk_level === 'high' ? 'مرتفع' : ($report->risk_level === 'medium' ? 'متوسط' : 'منخفض') }}</span><span class="source-tag">{{ $report->source === 'demo' ? 'بيانات عرض' : 'بلاغ مواطن' }}</span></div><h2><a href="{{ route('reports.show', $report->public_id) }}">{{ $report->title }}</a></h2><p>{{ Str::limit($report->description, 120) }}</p><div class="report-card-meta"><span>{{ $report->area_name }}</span><time datetime="{{ $report->created_at->toIso8601String() }}">{{ $report->created_at->diffForHumans() }}</time></div><a class="text-link" href="{{ route('reports.show', $report->public_id) }}">عرض التفاصيل <span>←</span></a></div>
                </article>
            @empty
                <div class="citizen-card empty-state">لا توجد بلاغات موثّقة بعد. يمكنك المساهمة ببلاغ واضح مع صورة وموقع.</div>
            @endforelse
        </div>
        <div class="pagination-wrap">{{ $reports->links() }}</div>
    </section>
</x-app-layout>
