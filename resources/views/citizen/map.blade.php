<x-app-layout>
    <section class="citizen-page">
        <div class="citizen-heading">
            <div><span class="eyebrow"><span class="live-dot"></span> رصد مَجرى</span><h1>خريطة المخاطر</h1><p>بلاغات موثّقة وإغلاقات وتنبيهات في مكان واحد. موقعك لا يُتابع في الخلفية.</p></div>
            <a class="button" href="{{ route('reports.create') }}">＋ أرسل بلاغاً</a>
        </div>
        <div class="map-legend" aria-label="مفتاح الخريطة">
            <span><i class="legend-dot danger"></i> خطر مرتفع</span><span><i class="legend-dot caution"></i> تنبيه</span><span><i class="legend-dot closure"></i> إغلاق طريق</span><span class="map-count">{{ $reports->count() }} بلاغاً موثّقاً</span>
        </div>
        <div id="majra-map" class="leaflet-map" aria-label="خريطة الأردن والبلاغات"></div>
        <div class="citizen-grid map-lower-grid">
            <section class="citizen-card"><div class="card-heading"><span class="card-icon">!</span><div><h2>التنبيهات الحالية</h2><p>المصدر والوقت ظاهرين مع كل تنبيه</p></div></div>
                @forelse($alerts as $alert)
                    <article class="mini-alert"><span class="severity-tag severity-{{ $alert->severity }}">{{ $alert->severity === 'critical' ? 'طارئ' : ($alert->severity === 'warning' ? 'تحذير' : 'معلومة') }}</span><div><strong>{{ $alert->title }}</strong><p>{{ $alert->message }}</p><small>{{ $alert->area_name ?: 'عام' }} · {{ $alert->source === 'demo' ? 'عرض تجريبي' : 'تنبيه رسمي' }}</small></div></article>
                @empty
                    <p class="empty-state">لا توجد تنبيهات نشطة الآن.</p>
                @endforelse
            </section>
            <section class="citizen-card"><div class="card-heading"><span class="card-icon blue">↗</span><div><h2>إغلاقات الطرق</h2><p>تحقق من المسار قبل الانطلاق</p></div></div>
                @forelse($closures as $closure)
                    <article class="closure-row"><strong>{{ $closure->area_name }}</strong><span>{{ $closure->title }}</span><small>{{ $closure->ends_at ? 'حتى '.$closure->ends_at->format('H:i') : 'مفتوح حتى التحديث' }}</small></article>
                @empty
                    <p class="empty-state">لا توجد إغلاقات مسجلة حالياً.</p>
                @endforelse
            </section>
        </div>
    </section>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const majraReports = {{ Illuminate\Support\Js::from($reports->map(fn ($report) => ['lat' => (float) $report->latitude, 'lng' => (float) $report->longitude, 'title' => $report->title, 'area' => $report->area_name, 'risk' => $report->risk_level, 'status' => $report->status, 'url' => route('reports.show', $report->public_id)])) }};
        const majraClosures = {{ Illuminate\Support\Js::from($closures->map(fn ($closure) => ['lat' => (float) $closure->latitude, 'lng' => (float) $closure->longitude, 'title' => $closure->title, 'area' => $closure->area_name])) }};
        const map = L.map('majra-map', { scrollWheelZoom: false }).setView([31.2, 36.2], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        const marker = (entry, type) => {
            const color = type === 'closure' ? '#a78bfa' : (entry.risk === 'high' ? '#fb654f' : '#f3bc55');
            const node = document.createElement('div');
            const title = document.createElement('strong'); title.textContent = entry.title; node.append(title);
            const area = document.createElement('div'); area.textContent = entry.area || 'الأردن'; node.append(area);
            if (entry.url) { const link = document.createElement('a'); link.href = entry.url; link.textContent = 'تفاصيل البلاغ'; node.append(link); }
            L.circleMarker([entry.lat, entry.lng], { radius: 8, color, fillColor: color, fillOpacity: .82, weight: 2 }).addTo(map).bindPopup(node);
        };
        majraReports.forEach((entry) => marker(entry, 'report'));
        majraClosures.forEach((entry) => marker(entry, 'closure'));
        if (navigator.geolocation) {
            const locate = document.createElement('button'); locate.type = 'button'; locate.className = 'map-locate'; locate.textContent = 'موقعي'; locate.setAttribute('aria-label', 'اعرض موقعي مرة واحدة');
            locate.addEventListener('click', () => navigator.geolocation.getCurrentPosition(({coords}) => map.setView([coords.latitude, coords.longitude], 13), () => alert('تعذّر تحديد الموقع. يمكنك متابعة الخريطة يدوياً.')));
            document.querySelector('#majra-map').append(locate);
        }
    </script>
</x-app-layout>
