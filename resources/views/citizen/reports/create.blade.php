<x-app-layout>
    <section class="citizen-page report-form-page">
        <a class="back-link" href="{{ route('map') }}">→ العودة للخريطة</a>
        <div class="citizen-heading"><div><span class="eyebrow">بلاغ مدروس يساعد الجميع</span><h1>أرسل بلاغاً عن خطر</h1><p>أضف صورة واضحة وموقعاً دقيقاً. سيبقى البلاغ قيد المراجعة قبل ظهوره للعامة.</p></div><span class="privacy-note">الموقع يُستخدم لهذا البلاغ فقط</span></div>
        @if($errors->any())<div class="form-errors" role="alert">تحقق من الحقول المعلّمة ثم أعد الإرسال.</div>@endif
        <form class="report-form citizen-card" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-grid">
                <label class="form-field full"><span>عنوان مختصر</span><input name="title" value="{{ old('title') }}" maxlength="140" required placeholder="مثال: مياه متجمعة قرب مدخل النفق"><small class="field-error">@error('title'){{ $message }}@enderror</small></label>
                <label class="form-field full"><span>ماذا يحدث؟</span><textarea name="description" rows="4" maxlength="2000" required placeholder="صف مستوى المياه، اتجاه الحركة، وأي تفاصيل تساعد الآخرين">{{ old('description') }}</textarea><small class="field-error">@error('description'){{ $message }}@enderror</small></label>
                <label class="form-field"><span>المنطقة أو الحي</span><input name="area_name" value="{{ old('area_name') }}" maxlength="120" required placeholder="عمّان، تلاع العلي"><small class="field-error">@error('area_name'){{ $message }}@enderror</small></label>
                <div class="form-field full location-choice"><span>اختر طريقة تحديد الموقع</span><div class="location-methods"><button type="button" class="button button-small location-button" id="locate-once">استخدام GPS لمرة واحدة</button><button type="button" class="button button-small map-choice-button" id="select-on-map">اختيار نقطة على الخريطة</button></div><small>لن يطلب مَجرى موقعك إلا بعد ضغط زر GPS وموافقتك في المتصفح. الموقع لا يُرسل إلا مع البلاغ.</small><small class="field-error">@error('latitude'){{ $message }}@enderror @error('longitude'){{ $message }}@enderror</small></div>
                <div class="form-field full"><div id="report-picker" class="picker-map" aria-label="اختر موقع البلاغ من الخريطة"></div><input type="hidden" name="latitude" id="report-lat" value="{{ old('latitude') }}"><input type="hidden" name="longitude" id="report-lng" value="{{ old('longitude') }}"><p class="coordinate-hint" id="coordinate-hint" role="status" aria-live="polite">يمكنك السماح للمتصفح بتحديد موقعك مرة واحدة عبر GPS، أو الضغط على نقطة في الخريطة. لن يُرسل الموقع إلا بعد إرسال البلاغ.</p></div>
                <label class="form-field full"><span>صورة للموقع</span><input type="file" name="photo" id="report-photo" accept="image/jpeg,image/png,image/webp" required><small>JPG أو PNG أو WebP · حتى 8 ميغابايت</small><small class="field-error">@error('photo'){{ $message }}@enderror</small><img id="photo-preview" class="photo-preview" alt="معاينة الصورة المختارة" hidden></label>
            </div>
            <div class="form-actions"><button class="button" type="submit">إرسال للمراجعة <span>←</span></button><span>لن تُمنح نقاط قبل اعتماد البلاغ.</span></div>
        </form>
    </section>
    @vite('resources/js/maps.js')
    <script type="module">
        const L = window.L;
        const picker = L.map('report-picker').setView([31.2, 36.2], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(picker);
        let chosenMarker;
        const setPoint = (lat, lng, source = 'map') => {
            document.getElementById('report-lat').value = lat.toFixed(7);
            document.getElementById('report-lng').value = lng.toFixed(7);
            document.getElementById('coordinate-hint').textContent = `${source === 'gps' ? 'تم تحديد الموقع عبر GPS لمرة واحدة' : 'تم اختيار الموقع من الخريطة'} · ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            if (chosenMarker) {
                chosenMarker.setLatLng([lat, lng]);
            } else {
                chosenMarker = L.circleMarker([lat, lng], { radius: 8, color: '#087f83', fillOpacity: 0.85 }).addTo(picker);
            }
        };
        picker.on('click', ({latlng}) => setPoint(latlng.lat, latlng.lng));
        const locationHint = document.getElementById('coordinate-hint');
        const gpsButton = document.getElementById('locate-once');
        gpsButton.addEventListener('click', () => {
            if (!navigator.geolocation) {
                locationHint.textContent = 'المتصفح لا يدعم GPS. اختر نقطة يدوياً على الخريطة.';
                return;
            }
            gpsButton.disabled = true;
            gpsButton.setAttribute('aria-busy', 'true');
            gpsButton.textContent = 'جارٍ تحديد الموقع…';
            navigator.geolocation.getCurrentPosition(({coords}) => {
                gpsButton.disabled = false;
                gpsButton.removeAttribute('aria-busy');
                gpsButton.textContent = 'استخدام GPS لمرة واحدة';
                const {latitude, longitude} = coords;
                if (latitude < 29 || latitude > 33.5 || longitude < 34.8 || longitude > 39.4) {
                    locationHint.textContent = 'الموقع المحدد خارج نطاق الأردن. اختر نقطة داخل الأردن على الخريطة.';
                    return;
                }
                setPoint(latitude, longitude, 'gps');
                picker.setView([latitude, longitude], 14);
            }, (error) => {
                gpsButton.disabled = false;
                gpsButton.removeAttribute('aria-busy');
                gpsButton.textContent = 'استخدام GPS لمرة واحدة';
                locationHint.textContent = error.code === 1
                    ? 'لم تسمح بالوصول للموقع. اختر نقطة يدوياً على الخريطة.'
                    : 'تعذّر تحديد موقعك الآن. اختر نقطة يدوياً على الخريطة.';
            }, {enableHighAccuracy: true, maximumAge: 30000, timeout: 12000});
        });
        document.getElementById('select-on-map').addEventListener('click', () => {
            document.getElementById('report-picker').scrollIntoView({block: 'center'});
            picker.invalidateSize();
            locationHint.textContent = 'اضغط على نقطة داخل الخريطة لتحديد الموقع؛ أو استخدم GPS لمرة واحدة.';
        });
        document.getElementById('report-photo').addEventListener('change', (event) => { const file = event.target.files[0]; const preview = document.getElementById('photo-preview'); if (file) { preview.src = URL.createObjectURL(file); preview.hidden = false; } });
        const oldLat = Number(document.getElementById('report-lat').value), oldLng = Number(document.getElementById('report-lng').value);
        if (oldLat && oldLng) { picker.setView([oldLat, oldLng], 13); setPoint(oldLat, oldLng); }
    </script>
</x-app-layout>
