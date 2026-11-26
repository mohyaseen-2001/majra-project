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
                <div class="form-field"><span>حدّد الموقع على الخريطة</span><button type="button" class="button button-small location-button" id="locate-once">استخدم موقعي مرة واحدة</button><small class="field-error">@error('latitude'){{ $message }}@enderror</small></div>
                <div class="form-field full"><div id="report-picker" class="picker-map" aria-label="اختر موقع البلاغ"></div><input type="hidden" name="latitude" id="report-lat" value="{{ old('latitude') }}"><input type="hidden" name="longitude" id="report-lng" value="{{ old('longitude') }}"><p class="coordinate-hint" id="coordinate-hint">اضغط على الخريطة لاختيار النقطة؛ الموقع يُرسل بعد ضغطك على الإرسال فقط.</p></div>
                <label class="form-field full"><span>صورة للموقع</span><input type="file" name="photo" id="report-photo" accept="image/jpeg,image/png,image/webp" required><small>JPG أو PNG أو WebP · حتى 8 ميغابايت</small><small class="field-error">@error('photo'){{ $message }}@enderror</small><img id="photo-preview" class="photo-preview" alt="معاينة الصورة المختارة" hidden></label>
            </div>
            <div class="form-actions"><button class="button" type="submit">إرسال للمراجعة <span>←</span></button><span>لن تُمنح نقاط قبل اعتماد البلاغ.</span></div>
        </form>
    </section>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const picker = L.map('report-picker').setView([31.2, 36.2], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(picker);
        let chosenMarker;
        const setPoint = (lat, lng) => {
            document.getElementById('report-lat').value = lat.toFixed(7);
            document.getElementById('report-lng').value = lng.toFixed(7);
            document.getElementById('coordinate-hint').textContent = `تم تحديد النقطة ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            chosenMarker ? chosenMarker.setLatLng([lat, lng]) : chosenMarker = L.marker([lat, lng]).addTo(picker);
        };
        picker.on('click', ({latlng}) => setPoint(latlng.lat, latlng.lng));
        document.getElementById('locate-once').addEventListener('click', () => navigator.geolocation?.getCurrentPosition(({coords}) => { setPoint(coords.latitude, coords.longitude); picker.setView([coords.latitude, coords.longitude], 14); }, () => alert('تعذّر تحديد الموقع. اختر النقطة يدوياً على الخريطة.')));
        document.getElementById('report-photo').addEventListener('change', (event) => { const file = event.target.files[0]; const preview = document.getElementById('photo-preview'); if (file) { preview.src = URL.createObjectURL(file); preview.hidden = false; } });
        const oldLat = Number(document.getElementById('report-lat').value), oldLng = Number(document.getElementById('report-lng').value);
        if (oldLat && oldLng) { picker.setView([oldLat, oldLng], 13); setPoint(oldLat, oldLng); }
    </script>
</x-app-layout>
