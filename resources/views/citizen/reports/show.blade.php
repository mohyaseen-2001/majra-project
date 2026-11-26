<x-app-layout>
    <section class="citizen-page report-detail-page">
        <a class="back-link" href="{{ route('feed') }}">→ العودة للموجز</a>
        @if(session('success'))<div class="success-banner" role="status">{{ session('success') }}</div>@endif
        <div class="citizen-heading"><div><span class="eyebrow">تفاصيل البلاغ · {{ $report->public_id }}</span><h1>{{ $report->title }}</h1><p>{{ $report->area_name }} · {{ $report->created_at->format('Y-m-d H:i') }}</p></div><span class="status-pill status-{{ $report->status }}">{{ ['pending'=>'قيد المراجعة','ai_checked'=>'فُحص آلياً','approved'=>'معتمد','rejected'=>'مرفوض','expired'=>'منتهي','resolved'=>'انتهى الخطر'][$report->status] ?? 'قيد المراجعة' }}</span></div>
        <div class="citizen-grid detail-grid">
            <article class="citizen-card report-detail-copy"><div class="detail-tags"><span class="risk-tag risk-{{ $report->risk_level }}">{{ $report->risk_level === 'unknown' ? 'بانتظار تقييم الخطر' : $report->risk_level }}</span><span class="source-tag">{{ $report->source === 'demo' ? 'بيانات عرض تجريبي' : 'مصدر المعلومة: بلاغ مواطن' }}</span></div><h2>وصف الحالة</h2><p>{{ $report->description }}</p><div class="report-detail-meta"><span>مقدم البلاغ: {{ $report->user?->name ?? 'مستخدم محذوف' }}</span><span>المراجعة النهائية: {{ $report->reviewed_at?->diffForHumans() ?? 'لم تتم بعد' }}</span></div>
                @if($report->aiAnalyses->first())<div class="ai-note"><strong>نتيجة الفحص المساعد</strong><p>توصية آلية للمراجعة فقط؛ القرار النهائي لفريق مَجرى.</p><small>{{ $report->aiAnalyses->first()->status === 'failed' ? 'تعذر الفحص الآلي، البلاغ بانتظار المراجعة اليدوية.' : 'تم حفظ نتيجة الفحص.' }}</small></div>@endif
                <button class="button button-small" id="share-report" type="button">نسخ رابط البلاغ</button>
            </article>
            <aside class="citizen-card report-media-card">@forelse($report->media as $media)<img src="{{ Storage::disk($media->disk)->url($media->path) }}" alt="صورة مرفقة بالبلاغ" loading="lazy">@empty<div class="photo-placeholder">لا توجد صورة</div>@endforelse</aside>
        </div>
    </section>
    <script>document.getElementById('share-report')?.addEventListener('click', async (event) => { await navigator.clipboard.writeText(location.href); event.currentTarget.textContent = 'تم نسخ الرابط'; });</script>
</x-app-layout>
