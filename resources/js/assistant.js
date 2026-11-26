import axios from 'axios';

const panel = document.querySelector('[data-safety-assistant]');
if (panel) {
    const form = panel.querySelector('form');
    const input = panel.querySelector('input');
    const messages = panel.querySelector('[data-chat-messages]');
    const fallback = (question) => {
        const q = question.toLowerCase();
        if (/خطر مباشر|في خطر|عالق|غريق|مصاب|محاصر|حادث/.test(q)) return 'إذا كان هناك خطر مباشر، ابتعد إلى مكان آمن واتصل بالطوارئ الموحد 911 فوراً. لا تدخل المياه ولا تعرّض نفسك للخطر.';
        if (/رقم|طوارئ|إسعاف|اسعاف/.test(q)) return 'رقم الطوارئ الموحد 911. تذكر بوابة الأردن أيضاً الإسعاف 193 والإطفاء 199. المصدر: jordan.gov.jo.';
        if (/بلاغ|أرسل|ارسل|صورة|موقع/.test(q)) return 'سجّل الدخول كمواطن، افتح «أرسل بلاغاً»، أضف صورة من مكان آمن وموقعاً ووصفاً. يبقى البلاغ قيد المراجعة.';
        if (/مستشفى|مستشفيات|قريب/.test(q)) return 'استخدم خيار المستشفيات في الخريطة لعرض بيانات OpenStreetMap المتاحة. عند الطوارئ اتصل بـ911.';
        if (/لون|الخريطة|خطر/.test(q)) return 'تحقق من مستوى الخطر وحالة البلاغ ووقت تحديثه ومصدره. الألوان لا تغني عن تعليمات الجهات الرسمية.';
        return 'أستطيع المساعدة بالسلامة والبلاغات وألوان الخريطة والطوارئ والمستشفيات. راجع دليل السلامة، واتصل بـ911 عند الخطر المباشر.';
    };
    const append = (text, kind = 'assistant') => {
        const bubble = document.createElement('p'); bubble.className = `chat-bubble ${kind}`; bubble.textContent = text; messages.append(bubble); messages.scrollTop = messages.scrollHeight;
    };
    form.addEventListener('submit', async (event) => {
        event.preventDefault(); const question = input.value.trim(); if (!question) return;
        append(question, 'user'); input.value = ''; input.disabled = true;
        try { const { data } = await axios.post('/api/safety-assistant', { question }); append(data.answer); }
        catch { append(fallback(question)); }
        finally { input.disabled = false; input.focus(); }
    });
    panel.querySelectorAll('[data-chat-prompt]').forEach((button) => button.addEventListener('click', () => { input.value = button.dataset.chatPrompt; form.requestSubmit(); }));
    panel.querySelector('[data-chat-toggle]')?.addEventListener('click', () => { const open = panel.classList.toggle('chat-open'); panel.querySelector('[data-chat-toggle]').setAttribute('aria-expanded', String(open)); if (open) input.focus(); });
}
