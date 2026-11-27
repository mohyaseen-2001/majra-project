import axios from 'axios';

const rows = document.getElementById('resource-rows');
if (rows && window.majraAdminResource) {
    const config = window.majraAdminResource;
    const modal = document.getElementById('resource-modal');
    const form = document.getElementById('resource-form');
    const fieldBox = document.getElementById('resource-fields');
    const toast = document.getElementById('admin-toast');
    const state = { page: 1, lastPage: 1, id: null, timer: null };
    const selectValues = {
        role: [['citizen','مواطن'],['company','شركة'],['admin','أدمن']],
        'closure-status': [['active','نشط'],['closed','مغلق'],['demo','عرض تجريبي']], severity: [['info','معلومة'],['warning','تحذير'],['critical','حرج']],
        source: [['admin','الإدارة'],['official','رسمي'],['demo','تجريبي']], 'company-status': [['pending','قيد المراجعة'],['approved','معتمد'],['rejected','مرفوض'],['archived','مؤرشف']],
        'subscription-status': [['pending','معلق'],['active','نشط'],['cancelled','ملغى'],['expired','منتهي'],['past_due','متأخر']],
        'payment-status': [['pending','معلق'],['paid','مدفوع'],['failed','فشل'],['refunded','مسترد']], provider: [['stripe','Stripe'],['manual','يدوي']],
        'contact-status': [['new','جديد'],['read','مقروء'],['replied','تم الرد'],['archived','مؤرشف']], interval: [['month','شهري'],['year','سنوي']],
    };
    const notify = (message, error = false) => { toast.textContent = message; toast.classList.toggle('error', error); toast.classList.add('visible'); setTimeout(() => toast.classList.remove('visible'), 3200); };
    const labelFor = (type, value) => selectValues[type]?.find(([key]) => String(key) === String(value))?.[1] || (typeof value === 'boolean' ? (value ? 'نعم' : 'لا') : (value ?? '—'));
    const load = async () => {
        rows.innerHTML = '<tr><td colspan="8">جارٍ التحميل…</td></tr>';
        try {
            const { data } = await axios.get(config.endpoint, { params: { page: state.page, search: document.getElementById('resource-search').value } });
            state.lastPage = data.meta.last_page; document.getElementById('resource-total').textContent = `${data.meta.total} سجل`; document.getElementById('resource-page').textContent = `${state.page} / ${state.lastPage}`; rows.replaceChildren();
            if (!data.data.length) { rows.innerHTML = '<tr><td colspan="8">لا توجد سجلات.</td></tr>'; return; }
            data.data.forEach((item) => {
                const row = document.createElement('tr'); const id = document.createElement('td'); id.textContent = item.id; row.append(id);
                config.fields.slice(0,5).forEach(([key,,type]) => { const td = document.createElement('td'); td.textContent = type === 'lines' ? (item[key] || []).join('، ') : labelFor(type, item[key]); row.append(td); });
                const actions = document.createElement('td'); actions.className = 'table-actions'; const edit = document.createElement('button'); edit.type='button'; edit.className='row-action'; edit.textContent='تعديل'; edit.addEventListener('click',()=>open(item)); actions.append(edit); const remove = document.createElement('button'); remove.type='button'; remove.className='row-action row-delete'; remove.textContent='حذف'; remove.addEventListener('click',()=>destroy(item)); actions.append(remove); row.append(actions); rows.append(row);
            });
        } catch (error) { rows.innerHTML = '<tr><td colspan="8">تعذر تحميل البيانات.</td></tr>'; notify(error.response?.data?.message || 'تعذر الاتصال بواجهة البيانات.', true); }
    };
    const open = (item = null) => {
        state.id = item?.id ?? null; fieldBox.replaceChildren(); document.getElementById('resource-modal-title').textContent = item ? `تعديل سجل #${item.id}` : 'إضافة سجل';
        config.fields.forEach(([key,label,type,required]) => {
            const wrapper = document.createElement('label'); wrapper.className='modal-field'; const caption = document.createElement('span'); caption.textContent=label; wrapper.append(caption);
            let input;
            if (selectValues[type]) { input=document.createElement('select'); selectValues[type].forEach(([value,text])=>{const option=document.createElement('option');option.value=value;option.textContent=text;input.append(option);}); }
            else if (type==='boolean') { input=document.createElement('input'); input.type='checkbox'; }
            else if (type==='textarea'||type==='lines') { input=document.createElement('textarea'); input.rows=type==='lines'?3:4; }
            else { input=document.createElement('input'); input.type=type.startsWith('company-') ? ({'company-name':'text','company-registration':'text','company-business':'text','company-phone':'tel','company-website':'url'}[type]) : type; }
            input.name=key; input.dataset.type=type; input.required=Boolean(required && (key!=='password'||!item));
            if (type.startsWith('company-') && config.section === 'users') {
                wrapper.dataset.companyOnly = 'true';
                wrapper.dataset.requiredForCompany = String(Boolean(required));
            }
            let value=item?.[key] ?? '';
            if(type==='lines') value=Array.isArray(value)?value.join('\n'):value;
            if(type==='datetime-local'&&value) value=new Date(value).toISOString().slice(0,16);
            if(type==='boolean') input.checked=Boolean(value); else if(value!==null) input.value=value;
            wrapper.append(input); fieldBox.append(wrapper);
        });
        if (config.section === 'users') {
            const role = fieldBox.querySelector('[name="role"]');
            const syncCompanyFields = () => fieldBox.querySelectorAll('[data-company-only]').forEach((wrapper) => {
                const visible = role.value === 'company';
                wrapper.hidden = !visible;
                wrapper.querySelector('input').required = visible && wrapper.dataset.requiredForCompany === 'true';
            });
            role.addEventListener('change', syncCompanyFields);
            syncCompanyFields();
        }
        modal.showModal();
    };
    const destroy = async (item) => {
        if (!window.confirm(`حذف السجل #${item.id}؟`)) return;
        try { await axios.delete(`${config.endpoint}/${item.id}`); notify('تم حذف السجل.'); load(); } catch(error) { notify(error.response?.data?.message||'تعذر حذف السجل.',true); }
    };
    document.getElementById('resource-create').addEventListener('click',()=>open());
    document.getElementById('resource-search').addEventListener('input',()=>{clearTimeout(state.timer);state.timer=setTimeout(()=>{state.page=1;load();},250);});
    document.getElementById('resource-prev').addEventListener('click',()=>{if(state.page>1){state.page--;load();}});
    document.getElementById('resource-next').addEventListener('click',()=>{if(state.page<state.lastPage){state.page++;load();}});
    form.addEventListener('submit',async(event)=>{
        event.preventDefault(); const payload={};
        fieldBox.querySelectorAll('input,select,textarea').forEach(input=>{const type=input.dataset.type;let value=type==='boolean'?input.checked:input.value;if(type==='number'&&value!=='')value=Number(value);if(type==='lines')value=value.split('\n').map(line=>line.trim()).filter(Boolean);if(type==='password'&&!value)return;if(type==='datetime-local'&&value)value=new Date(value).toISOString();payload[input.name]=value;});
        try { if(state.id) await axios.patch(`${config.endpoint}/${state.id}`,payload); else await axios.post(config.endpoint,payload); modal.close();notify('تم حفظ السجل.');load(); } catch(error) { notify(error.response?.data?.message||'راجع البيانات المدخلة.',true); }
    });
    load();
}
