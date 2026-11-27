<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07111e">
    <meta name="description" content="مَجرى — منصة أردنية لرصد مخاطر السيول والطرق المتأثرة.">
    <title>مَجرى | اعرف الخطر قبل ما يصير بطريقك</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/gsap@3.15.0/dist/gsap.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="majra-site">
    <div class="site-noise" aria-hidden="true"></div>
    @include('layouts.public-header')

    <main>
        <section class="hero-section" aria-labelledby="hero-title">
            <div class="hero-copy">
                <div class="eyebrow"><span class="live-dot"></span> منصة أردنية للإنذار المبكر</div>
                <h1 id="hero-title">اعرف الخطر<br><em>قبل ما يصير بطريقك</em></h1>
                <p class="hero-lede">صورة أوضح عن السيول وإغلاقات الطرق — تجمع البلاغات والتنبيهات في مكان واحد، لتوصل المعلومة للناس وقت ما تفرق.</p>
                <div class="hero-actions">
                    <a class="button" href="{{ auth()->check() ? (auth()->user()->hasRole('citizen') ? route('map') : (auth()->user()->hasRole('company') ? route('company.dashboard') : route('admin.dashboard'))) : route('login') }}">{{ auth()->check() && auth()->user()->hasRole('citizen') ? 'افتح الخريطة' : (auth()->check() ? 'لوحة المتابعة' : 'سجّل الدخول') }} <span aria-hidden="true">←</span></a>
                    <a class="text-link" href="#how-it-works">اكتشف الفكرة <span aria-hidden="true">↓</span></a>
                </div>
                <div class="hero-note"><span class="note-icon" aria-hidden="true">⌖</span><span>ما بنتتبع موقعك بالخلفية.<br><b>موقعك إلك — بتشاركه بإذنك.</b></span></div>
            </div>

            <div class="radar-wrap" aria-label="تصور بصري لغرفة عمليات مَجرى">
                <div class="radar-glow"></div>
                <div class="radar-card">
                    <div class="radar-topline"><span class="panel-kicker">رصد مَجرى <b>مركز العمليات</b></span><span class="panel-time"><i></i> مصادر البيانات متصلة</span></div>
                    <div class="map-scene" id="map-preview">
                        <div class="contour contour-one"></div><div class="contour contour-two"></div><div class="contour contour-three"></div>
                        <svg class="jordan-outline" viewBox="0 0 300 390" role="img" aria-label="رسم توضيحي لخريطة الأردن">
                            <path d="M115 18 176 28 196 63 220 80 209 119 229 141 217 177 236 207 220 244 233 278 208 313 197 365 168 372 151 342 126 327 111 292 94 270 101 231 84 204 98 169 86 140 100 111 91 80 108 55Z" />
                            <path class="map-river" d="M109 76c18 21-2 39 12 55s-10 37 7 56-8 36 11 55-6 34 11 50-1 33 13 50" />
                            <circle class="city-point point-north" cx="155" cy="92" r="5"/><circle class="city-point point-central" cx="160" cy="184" r="6"/><circle class="city-point point-south" cx="180" cy="280" r="4"/>
                            <circle class="point-pulse" cx="160" cy="184" r="14"/>
                        </svg>
                        <span class="map-label label-amman">عمّان <i></i></span>
                        <span class="map-label label-irbid">إربد</span>
                        <span class="map-label label-aqaba">العقبة</span>
                        <span class="map-caption">معاينة توضيحية · افتح الخريطة للتفاصيل الحية</span>
                        <div class="map-legend"><span><i class="legend-blue"></i> مياه</span><span><i class="legend-amber"></i> تنبيه</span><span><i class="legend-red"></i> خطر</span></div>
                    </div>
                    <div class="radar-footer">
                        <div><span class="footer-label">الطقس</span><strong>Open‑Meteo · افتح الخريطة</strong></div>
                        <div><span class="footer-label">البلاغات</span><strong>مراجعة بشرية قبل النشر</strong></div>
                        <div class="signal"><span class="signal-bars"><i></i><i></i><i></i><i></i></span><span>حالة المنصة</span></div>
                    </div>
                </div>
                <div class="radar-coordinate">الأردن <span>خريطة عامة</span></div>
            </div>
        </section>

        <section class="mission-strip" id="how-it-works">
            <div class="strip-label"><span>01</span><div><small>الفكرة</small><strong>من المعلومة إلى قرار أوضح</strong></div></div>
            <p>بلاغات المواطنين، فحص الصورة بالذكاء الاصطناعي، ومراجعة بشرية — ضمن مسار واحد يوضح مصدر كل معلومة.</p>
            <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" aria-label="مَجرى">←</a>
        </section>

        <section class="roles-section" id="for-who">
            <div class="section-heading"><div><span class="eyebrow">منظومة واحدة، أدوار واضحة</span><h2>كل طرف يضيف <em>صورة أوضح</em></h2></div><span class="section-index">02 / 04</span></div>
            <div class="role-grid">
                <article class="role-card role-citizen"><span class="role-number">01 / المواطن</span><span class="role-icon" aria-hidden="true">⌖</span><h3>بلّغ. تابع. خليك بأمان.</h3><p>شارك موقع الخطر وصورته، تابع حالة بلاغك، واطّلع على دليل السلامة.</p><span class="role-line"></span></article>
                <article class="role-card role-ai"><span class="role-number">02 / التحقق</span><span class="role-icon" aria-hidden="true">◉</span><h3>تحقق يساعد المراجعة</h3><p>تحليل الصورة يعطي توصية ومؤشر ثقة؛ القرار النهائي يظل للمراجع البشري.</p><span class="role-line"></span></article>
                <article class="role-card role-ops"><span class="role-number">03 / العمليات</span><span class="role-icon" aria-hidden="true">⌘</span><h3>معلومة موثّقة، قرار أسرع</h3><p>الأدمن يراجع البلاغات ويدير التنبيهات وإغلاقات الطرق من لوحة واحدة.</p><span class="role-line"></span></article>
                <article class="role-card role-company"><span class="role-number">04 / الشركات</span><span class="role-icon" aria-hidden="true">▤</span><h3>راقب مناطق عملك</h3><p>مساحات متابعة وتقارير مخاطر تساعد فرق التشغيل على الاستعداد.</p><span class="role-line"></span></article>
            </div>
        </section>
    </main>

    <footer class="site-footer"><a class="brand" href="{{ route('home') }}"><span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span><span>مَجرى<small>Orange Coding Academy · مشروع تخرج</small></span></a><span>المعلومة الموثوقة تصنع فرقاً.</span><span>الأردن · <span id="year">{{ date('Y') }}</span></span></footer>
</body>
</html>
