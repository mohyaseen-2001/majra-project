<x-app-layout>
    <div class="ops-heading">
        <div><span class="eyebrow"><span class="live-dot"></span> مركز المتابعة</span><h1>أهلاً، {{ auth()->user()->name }}</h1><p>هذه مساحة مَجرى الخاصة بك. نجهّز الآن أدوات الرصد والتحقق.</p></div>
        <span class="status-pill"><span class="live-dot"></span> مرحلة التأسيس</span>
    </div>
    <div class="ops-stats">
        <div class="ops-stat"><span>حالة الدخول</span><strong>جلسة آمنة</strong></div>
        <div class="ops-stat"><span>حالة الحساب</span><strong><i>●</i> مفعّل</strong></div>
        <div class="ops-stat"><span>المنطقة الزمنية</span><strong>عمّان · الأردن</strong></div>
    </div>
    <section class="ops-panel"><h2>غرفة العمليات تُبنى الآن</h2><p>سيرتبط هذا المكان بالخريطة والبلاغات والتنبيهات في المراحل القادمة. حالياً، تم تجهيز حسابك وبنية الواجهة الأساسية لمَجرى.</p></section>
</x-app-layout>
