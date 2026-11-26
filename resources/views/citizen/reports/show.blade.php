<x-app-layout>
    <section class="citizen-page report-detail-page">
        <a class="back-link" href="{{ route('feed') }}">→ العودة للموجز</a>
        @if(session('success'))<div class="success-banner" role="status">{{ session('success') }}</div>@endif
        <div class="citizen-heading"><div><span class="eyebrow">تفاصيل البلاغ · {{ $report->public_id }}</span><h1>{{ $report->title }}</h1><p>{{ $report->area_name }} · {{ $report->created_at->format('Y-m-d H:i') }}</p></div><span class="status-pill status-{{ $report->status }}">{{ ['pending'=>'قيد المراجعة','ai_checked'=>'فُحص آلياً','approved'=>'معتمد','rejected'=>'مرفوض','expired'=>'منتهي','resolved'=>'انتهى الخطر'][$report->status] ?? 'قيد المراجعة' }}</span></div>
        <div class="citizen-grid detail-grid">
            <article class="citizen-card report-detail-copy"><div class="detail-tags"><span class="risk-tag risk-{{ $report->risk_level }}">{{ $report->risk_level === 'unknown' ? 'بانتظار تقييم الخطر' : $report->risk_level }}</span><span class="source-tag">{{ $report->source === 'demo' ? 'بيانات عرض تجريبي' : 'مصدر المعلومة: بلاغ مواطن' }}</span></div><h2>وصف الحالة</h2><p>{{ $report->description }}</p><div class="report-detail-meta"><span>مقدم البلاغ: {{ $report->user?->name ?? 'مستخدم محذوف' }}</span><span>المراجعة النهائية: {{ $report->reviewed_at?->diffForHumans() ?? 'لم تتم بعد' }}</span></div>
                @if($report->aiAnalyses->first())<div class="ai-note"><strong>نتيجة الفحص المساعد</strong><p>توصية آلية للمراجعة فقط؛ القرار النهائي لفريق مَجرى.</p><small>{{ in_array($report->aiAnalyses->first()->status, ['failed','unavailable']) ? 'تعذر الفحص الآلي، البلاغ بانتظار المراجعة اليدوية.' : 'تم حفظ نتيجة الفحص.' }}</small></div>@endif
                <button class="button button-small" id="share-report" type="button">نسخ رابط البلاغ</button>
                @if($report->status === 'approved' && (! $report->expires_at || $report->expires_at->isFuture()))
                    <div class="report-confirmation"><strong>هل الخطر ما زال موجوداً؟</strong><p>{{ $report->still_present_count }} أكدوا استمرار الخطر · {{ $report->ended_count }} أفادوا بانتهائه</p><form method="POST" action="{{ route('reports.confirm', $report->public_id) }}">@csrf<button class="admin-button ghost" name="choice" value="still_present" type="submit" {{ $myConfirmation === 'still_present' ? 'disabled' : '' }}>ما زال الخطر موجوداً</button><button class="admin-button ghost" name="choice" value="ended" type="submit" {{ $myConfirmation === 'ended' ? 'disabled' : '' }}>انتهى الخطر</button></form></div>
                @endif
            </article>
            <aside class="citizen-card report-media-card">@forelse($report->media as $media)<img src="{{ Storage::disk($media->disk)->url($media->path) }}" alt="صورة مرفقة بالبلاغ" loading="lazy">@empty<div class="photo-placeholder">لا توجد صورة</div>@endforelse</aside>
        </div>
    </section>
    <script>document.getElementById('share-report')?.addEventListener('click', async (event) => { await navigator.clipboard.writeText(location.href); event.currentTarget.textContent = 'تم نسخ الرابط'; });</script>
</x-app-layout>
