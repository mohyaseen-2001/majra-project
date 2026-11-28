<x-app-layout>
    <section class="citizen-page">
        <div class="citizen-heading">
            <div><span class="eyebrow"><span class="live-dot"></span> رصد مَجرى</span><h1>خريطة المخاطر</h1><p>بلاغات موثّقة وإغلاقات وتنبيهات في مكان واحد. موقعك لا يُتابع في الخلفية.</p></div>
            <a class="button" href="{{ route('reports.create') }}">＋ أرسل بلاغاً</a>
        </div>
        <div class="map-legend" aria-label="مفتاح الخريطة">
            <span><i class="legend-dot danger"></i> خطر مرتفع</span><span><i class="legend-dot caution"></i> تنبيه</span><span><i class="legend-dot closure"></i> إغلاق طريق</span><span class="map-count">{{ $reports->count() }} بلاغاً موثّقاً</span>
        </div>
        <div class="map-data-strip"><article class="weather-card"><span class="weather-symbol">☂</span><div><small>طقس عمّان · <span id="weather-source">Open-Meteo</span></small><strong id="weather-main">جارٍ جلب آخر تحديث…</strong><span id="weather-extra">إذا تعذّر المصدر ستظهر آخر بيانات محفوظة.</span></div><button type="button" id="weather-refresh">تحديث</button></article><article class="hospital-card"><div><strong>مستشفيات قريبة</strong><small id="hospital-status" role="status" aria-live="polite">مصدر مفتوح من OpenStreetMap وقد تكون بياناته غير مكتملة.</small><small id="location-status" role="status" aria-live="polite"></small><a id="hospital-fallback" class="text-link" href="https://www.google.com/maps/search/?api=1&amp;query=hospitals" target="_blank" rel="noopener noreferrer" hidden>ابحث عن المستشفيات في خرائط Google</a></div><button type="button" class="button button-small" id="hospitals-load">اعرض حول مركز الخريطة</button></article></div>
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

    @vite('resources/js/maps.js')
    <script type="module">
        const L = window.L;
        const majraReports = {{ Illuminate\Support\Js::from($reports->map(fn ($report) => ['lat' => (float) $report->latitude, 'lng' => (float) $report->longitude, 'title' => $report->title, 'area' => $report->area_name, 'risk' => $report->risk_level, 'automatic' => $report->isAutomaticallyPublished(), 'status' => $report->status, 'url' => route('reports.show', $report->public_id)])) }};
        const majraClosures = {{ Illuminate\Support\Js::from($closures->map(fn ($closure) => ['lat' => (float) $closure->latitude, 'lng' => (float) $closure->longitude, 'title' => $closure->title, 'area' => $closure->area_name])) }};
        const map = L.map('majra-map', { scrollWheelZoom: false }).setView([31.2, 36.2], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        const marker = (entry, type) => {
            const color = type === 'closure' ? '#a78bfa' : (entry.risk === 'high' ? '#fb654f' : '#f3bc55');
            const node = document.createElement('div');
            const title = document.createElement('strong'); title.textContent = entry.title; node.append(title);
            const area = document.createElement('div'); area.textContent = entry.area || 'الأردن'; node.append(area);
            if (entry.automatic) {
                const source = document.createElement('div');
                source.textContent = 'بلاغ مواطن · فحص آلي';
                node.append(source);
            }
            if (entry.url) { const link = document.createElement('a'); link.href = entry.url; link.textContent = 'تفاصيل البلاغ'; node.append(link); }
            L.circleMarker([entry.lat, entry.lng], { radius: 8, color, fillColor: color, fillOpacity: .82, weight: 2 }).addTo(map).bindPopup(node);
        };
        majraReports.forEach((entry) => marker(entry, 'report'));
        majraClosures.forEach((entry) => marker(entry, 'closure'));
        const loadWeather = async () => {
            const main = document.getElementById('weather-main'); const extra = document.getElementById('weather-extra');
            try {
                const {data} = await window.axios.get('/api/weather', {params:{latitude:31.9539, longitude:35.9106}});
                const current=data.current||{}; main.textContent=`${data.condition} · ${current.temperature_2m ?? '—'}°`; extra.textContent=`احتمال هطول ${data.precipitation_probability ?? '—'}٪ · رطوبة ${current.relative_humidity_2m ?? '—'}٪ · آخر تحديث ${data.updated_at || 'غير معروف'}${data.stale ? ' · بيانات محفوظة' : ''}`;
                document.getElementById('weather-source').textContent=data.source||'Open-Meteo';
            } catch { main.textContent='الطقس غير متاح حالياً'; extra.textContent='ستظهر آخر بيانات محفوظة عند توفرها.'; }
        };
        document.getElementById('weather-refresh').addEventListener('click',loadWeather); loadWeather();
        document.getElementById('hospitals-load').addEventListener('click',async(event)=>{
            const button=event.currentTarget; button.disabled=true; document.getElementById('hospital-status').textContent='جارٍ البحث حول مركز الخريطة…';
            const center=map.getCenter();
            try {
                document.getElementById('hospital-fallback').hidden = true;
                const {data}=await window.axios.get('/api/hospitals',{params:{latitude:center.lat,longitude:center.lng,radius_km:15}});
                data.data.forEach((place)=>{const node=document.createElement('div');const name=document.createElement('strong');name.textContent=place.name;node.append(name);if(place.phone){const phone=document.createElement('div');phone.textContent=place.phone;node.append(phone);}L.marker([place.latitude,place.longitude]).addTo(map).bindPopup(node);});
                document.getElementById('hospital-status').textContent=`${data.data.length} نتيجة · ${data.source} · ${data.stale?'بيانات محفوظة':'تحديث '+data.updated_at}`;
            } catch {
                document.getElementById('hospital-status').textContent = 'تعذر جلب المستشفيات. راجع المصادر الرسمية عند الحاجة.';
                document.getElementById('hospital-fallback').hidden = false;
            }
            finally { button.disabled=false; }
        });
        if (navigator.geolocation) {
            const locate = document.createElement('button'); locate.type = 'button'; locate.className = 'map-locate'; locate.textContent = 'موقعي'; locate.setAttribute('aria-label', 'اعرض موقعي مرة واحدة');
            locate.addEventListener('click', () => {
                const status = document.getElementById('location-status');
                locate.disabled = true;
                locate.textContent = 'جارٍ تحديد الموقع…';
                status.textContent = 'انتظر لحظة حتى يحدد المتصفح موقعك.';
                navigator.geolocation.getCurrentPosition(({coords}) => {
                    locate.disabled = false;
                    locate.textContent = 'موقعي';
                    map.setView([coords.latitude, coords.longitude], 13);
                    status.textContent = 'تم تحديد موقعك. اضغط «اعرض حول مركز الخريطة» لعرض المستشفيات القريبة.';
                }, (error) => {
                    locate.disabled = false;
                    locate.textContent = 'موقعي';
                    status.textContent = error.code === 1
                        ? 'المتصفح منع الوصول للموقع. اسمح به لهذا الموقع من إعدادات المتصفح ثم أعد المحاولة.'
                        : error.code === 3
                            ? 'انتهت مهلة تحديد الموقع. تأكد من تفعيل خدمات الموقع في الجهاز ثم أعد المحاولة.'
                            : 'تعذر الحصول على موقع الجهاز. تحقق من خدمات الموقع أو تابع باستخدام الخريطة يدويًا.';
                }, {enableHighAccuracy: false, maximumAge: 30000, timeout: 20000});
            });
            document.querySelector('#majra-map').append(locate);
        }
    </script>
</x-app-layout>
