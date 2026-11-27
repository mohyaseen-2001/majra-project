import axios from 'axios';

const body = document.getElementById('reports-rows');
if (body) {
    const drawer = document.getElementById('report-drawer');
    const backdrop = document.getElementById('drawer-backdrop');
    const toast = document.getElementById('admin-toast');
    const state = { page: 1, lastPage: 1, reportId: null, timer: null };
    const labels = { pending: 'قيد المراجعة', ai_checked: 'فُحص آلياً', approved: 'معتمد', rejected: 'مرفوض', expired: 'منتهي', resolved: 'انتهى الخطر' };

    const notify = (message, error = false) => {
        toast.textContent = message;
        toast.classList.toggle('error', error);
        toast.classList.add('visible');
        setTimeout(() => toast.classList.remove('visible'), 3200);
    };
    const cell = (row, value, className = '') => {
        const td = document.createElement('td');
        td.textContent = value ?? '—';
        if (className) td.className = className;
        row.append(td);
        return td;
    };
    const load = async () => {
        body.innerHTML = '<tr><td colspan="7">جارٍ تحميل البلاغات…</td></tr>';
        try {
            const { data } = await axios.get('/api/admin/reports', { params: { page: state.page, search: document.getElementById('report-search').value, status: document.getElementById('report-status').value, risk_level: document.getElementById('report-risk').value } });
            state.lastPage = data.meta.last_page;
            document.getElementById('reports-total').textContent = `${data.meta.total} بلاغ`;
            document.getElementById('reports-page').textContent = `${state.page} / ${state.lastPage}`;
            body.replaceChildren();
            if (!data.data.length) { body.innerHTML = '<tr><td colspan="7">لا توجد نتائج مطابقة.</td></tr>'; return; }
            data.data.forEach((report) => {
                const row = document.createElement('tr'); row.className = 'clickable-row'; row.tabIndex = 0;
                const title = cell(row, report.title, 'report-cell-title'); title.setAttribute('data-label', report.user?.name || 'مستخدم');
                cell(row, report.area_name);
                cell(row, report.source === 'demo' ? 'بيانات عرض' : (report.source === 'admin' ? 'الإدارة' : 'مواطن'));
                cell(row, ({critical:'حرج',high:'مرتفع',medium:'متوسط',low:'منخفض',unknown:'غير محدد'})[report.risk_level] || report.risk_level, `risk-text risk-${report.risk_level}`);
                cell(row, labels[report.status] || report.status, `status-${report.status}`);
                cell(row, new Date(report.created_at).toLocaleString('ar-JO', { dateStyle: 'medium', timeStyle: 'short' }));
                const action = document.createElement('td'); action.className = 'table-actions';
                const button = document.createElement('button'); button.className = 'row-action'; button.type = 'button'; button.textContent = 'تفاصيل'; action.append(button);
                const remove = document.createElement('button'); remove.className = 'row-action row-delete'; remove.type = 'button'; remove.textContent = 'حذف'; remove.setAttribute('aria-label', `حذف البلاغ ${report.title}`);
                remove.addEventListener('click', async (event) => {
                    event.preventDefault(); event.stopPropagation();
                    if (!window.confirm(`حذف البلاغ «${report.title}» نهائيًا؟`)) return;
                    try {
                        await axios.delete(`/api/admin/reports/${report.id}`);
                        notify('تم حذف البلاغ ومرفقاته.');
                        if (body.children.length === 1 && state.page > 1) state.page--;
                        load();
                    } catch (error) { notify(error.response?.data?.message || 'تعذر حذف البلاغ.', true); }
                });
                action.append(remove); row.append(action);
                row.addEventListener('click', () => open(report.id));
                row.addEventListener('keydown', (event) => { if (event.key === 'Enter') open(report.id); });
                body.append(row);
            });
        } catch (error) { body.innerHTML = '<tr><td colspan="7">تعذر تحميل البيانات.</td></tr>'; notify(error.response?.data?.message || 'تعذر الاتصال بواجهة البيانات.', true); }
    };
    const open = async (id) => {
        try {
            const { data: response } = await axios.get(`/api/admin/reports/${id}`);
            const report = response.data; state.reportId = report.id;
            document.getElementById('drawer-title').textContent = report.title;
            const fields = document.getElementById('drawer-fields'); fields.replaceChildren();
            [['المنطقة', report.area_name], ['الحالة', labels[report.status] || report.status], ['مستوى الخطر', report.risk_level], ['الموقع', `${report.latitude}, ${report.longitude}`], ['المصدر', report.source === 'demo' ? 'عرض تجريبي' : 'بلاغ مواطن'], ['صاحب البلاغ', report.user?.name], ['الوصف', report.description]].forEach(([label, value]) => { const dt = document.createElement('dt'); dt.textContent = label; const dd = document.createElement('dd'); dd.textContent = value || '—'; fields.append(dt, dd); });
            const photo = document.getElementById('drawer-photo');
            if (report.media?.[0]) { photo.src = `/storage/${encodeURI(report.media[0].path)}`; photo.hidden = false; } else { photo.hidden = true; }
            const analysis = report.ai_analyses?.[0]; document.getElementById('drawer-ai').textContent = analysis ? `${analysis.is_flood === null ? 'لم يُحسم نوع المشهد' : (analysis.is_flood ? 'يرجّح وجود سيول' : 'لا يرجّح وجود سيول')} · خطر ${analysis.risk_level || 'غير محدد'} · ثقة ${analysis.confidence ?? '—'}` : 'لم يُجرَ الفحص بعد.';
            document.getElementById('drawer-note').value = analysis?.admin_notes || '';
            document.getElementById('drawer-false-report').checked = Boolean(report.is_false_report);
            drawer.classList.add('open'); backdrop.classList.add('open'); drawer.setAttribute('aria-hidden', 'false');
        } catch (error) { notify(error.response?.data?.message || 'تعذر فتح تفاصيل البلاغ.', true); }
    };
    const close = () => { drawer.classList.remove('open'); backdrop.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); };
    const review = async (status) => {
        if (!state.reportId) return;
        try { await axios.patch(`/api/admin/reports/${state.reportId}`, { status, admin_note: document.getElementById('drawer-note').value, is_false_report: status === 'rejected' && document.getElementById('drawer-false-report').checked }); notify(status === 'approved' ? 'تم اعتماد البلاغ وإضافة نقاطه.' : 'تم رفض البلاغ.'); close(); load(); }
        catch (error) { notify(error.response?.data?.message || 'تعذر حفظ القرار.', true); }
    };

    document.getElementById('report-search').addEventListener('input', () => { clearTimeout(state.timer); state.timer = setTimeout(() => { state.page = 1; load(); }, 250); });
    document.getElementById('report-status').addEventListener('change', () => { state.page = 1; load(); });
    document.getElementById('report-risk').addEventListener('change', () => { state.page = 1; load(); });
    document.getElementById('reports-prev').addEventListener('click', () => { if (state.page > 1) { state.page--; load(); } });
    document.getElementById('reports-next').addEventListener('click', () => { if (state.page < state.lastPage) { state.page++; load(); } });
    document.getElementById('drawer-close').addEventListener('click', close); backdrop.addEventListener('click', close);
    document.querySelectorAll('[data-review]').forEach((button) => button.addEventListener('click', () => review(button.dataset.review)));
    load();
}
