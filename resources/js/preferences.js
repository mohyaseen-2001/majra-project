const translations = {
    'غرفة عمليات السيول': 'Flood Operations Center', 'غرفة العمليات': 'Operations Room', 'الخريطة': 'Map', 'البلاغات': 'Reports', 'أرسل بلاغاً': 'Submit a report',
    'لوحة الشركة': 'Company dashboard', 'مناطقي': 'My areas', 'التقارير': 'Reports', 'الاشتراك': 'Subscription', 'لوحة المتابعة': 'Dashboard', 'الملف الشخصي': 'Profile',
    'تسجيل الخروج': 'Log out', 'خروج': 'Log out', 'الرئيسية': 'Overview', 'إغلاقات الطرق': 'Road closures', 'التنبيهات': 'Alerts', 'المواطنون': 'Citizens', 'الشركات': 'Companies',
    'الاشتراكات': 'Subscriptions', 'المدفوعات': 'Payments', 'رسائل التواصل': 'Contact messages', 'الباقات': 'Plans', 'المراقبة والتحكم': 'Monitoring and control', 'عرض الموقع ↗': 'View website ↗',
    'الاتصال مستقر': 'Connection stable', 'نبض مَجرى': 'Majra status', 'مسؤول النظام': 'System administrator', 'منصة الإنذار المبكر الأردنية': 'Jordan Early Warning Platform', 'فتح القائمة': 'Open menu',
    'إدارة مَجرى': 'Majra management', 'إدارة آمنة بصلاحيات الأدمن وسجل واضح لكل عنصر.': 'Secure administrator access with a clear record for every item.', '＋ إضافة إغلاق طريق': '＋ Add road closure', '＋ إضافة تنبيه': '＋ Add alert',
    '＋ إضافة مواطن أو حساب شركة': '＋ Add citizen or company account', '＋ إضافة شركة وحسابها': '＋ Add company and account', '＋ إضافة باقة': '＋ Add plan', '＋ إضافة سجل': '＋ Add record',
    'بحث': 'Search', 'بحث السجلات': 'Search records', 'جارٍ التحميل': 'Loading', 'جارٍ التحميل…': 'Loading…', 'لا توجد سجلات.': 'No records found.', 'تعذر تحميل البيانات.': 'Could not load data.',
    'تعذر الاتصال بواجهة البيانات.': 'Could not reach the data service.', 'تعديل': 'Edit', 'حذف': 'Delete', 'تم حذف السجل.': 'Record deleted.', 'تعذر حذف السجل.': 'Could not delete the record.',
    'حفظ': 'Save', 'إلغاء': 'Cancel', 'إضافة سجل': 'Add record', 'إدارة سجل': 'Manage record', 'إغلاق': 'Close', 'السابق': 'Previous', 'التالي': 'Next', 'تم حفظ السجل.': 'Record saved.',
    'راجع البيانات المدخلة.': 'Check the information entered.', 'الاسم': 'Name', 'البريد الإلكتروني': 'Email', 'كلمة المرور المؤقتة': 'Temporary password', 'الدور': 'Role', 'مواطن': 'Citizen',
    'شركة': 'Company', 'أدمن': 'Admin', 'موقوف': 'Suspended', 'اسم الشركة عند اختيار دور شركة': 'Company name (for company role)', 'نشاط الشركة': 'Business type', 'اسم الشركة': 'Company name',
    'اسم حساب الشركة': 'Company account name', 'بريد حساب الشركة': 'Company account email', 'رقم التسجيل': 'Registration number', 'هاتف الشركة': 'Company phone', 'موقع الشركة': 'Company website',
    'عنوان التنبيه': 'Alert title', 'الرسالة': 'Message', 'الخطورة': 'Severity', 'المنطقة': 'Area', 'المصدر': 'Source', 'نشط': 'Active', 'العنوان': 'Title', 'الحالة': 'Status',
    'خط العرض': 'Latitude', 'خط الطول': 'Longitude', 'التحويلة': 'Detour', 'رقم الشركة': 'Company ID', 'رقم الباقة': 'Plan ID', 'المبلغ': 'Amount', 'العملة': 'Currency', 'المزوّد': 'Provider',
    'الموضوع': 'Subject', 'شهري': 'Monthly', 'سنوي': 'Yearly', 'إدارة رئيسية': 'Administration', 'تمت العودة من الدفع': 'Returned from checkout', 'شكراً لاشتراكك': 'Thanks for subscribing',
    'البلاغات العامة': 'Public reports', 'دليل السلامة': 'Safety guide', 'تسجيل الدخول': 'Log in', 'إنشاء حساب': 'Create account', 'كلمة المرور': 'Password', 'تذكرني': 'Remember me',
    'نسيت كلمة المرور؟': 'Forgot your password?', 'تأكيد كلمة المرور': 'Confirm password', 'تسجيل': 'Register', 'إرسال': 'Submit', 'حالة الاتصال': 'Connection status', 'الإشعارات': 'Notifications',
    'تنبيه': 'Alert', 'مستشفيات قريبة': 'Nearby hospitals', 'استخدم موقعي': 'Use my location', 'موقعي': 'My location', 'عرض التفاصيل': 'View details', 'عرض الكل': 'View all',
};

const originals = new WeakMap();
const language = () => localStorage.getItem('majra-language') || 'ar';
const applyLanguage = () => {
    const english = language() === 'en';
    document.documentElement.lang = english ? 'en' : 'ar';
    document.documentElement.dir = english ? 'ltr' : 'rtl';
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) {
        const parent = node.parentElement;
        if (!parent || ['SCRIPT', 'STYLE', 'TEXTAREA', 'CODE'].includes(parent.tagName)) continue;
        if (!originals.has(node)) originals.set(node, node.nodeValue);
        const original = originals.get(node);
        const trimmed = original.trim();
        if (!trimmed) continue;
        const translated = english ? translations[trimmed] : trimmed;
        if (!translated) continue;
        const start = original.match(/^\s*/)?.[0] || '';
        const end = original.match(/\s*$/)?.[0] || '';
        node.nodeValue = `${start}${translated}${end}`;
    }
    document.querySelectorAll('[placeholder],[aria-label],[title]').forEach((element) => {
        for (const attribute of ['placeholder', 'aria-label', 'title']) {
            const value = element.getAttribute(attribute);
            if (!value) continue;
            const key = `data-majra-original-${attribute}`;
            if (!element.hasAttribute(key)) element.setAttribute(key, value);
            const original = element.getAttribute(key);
            if (english && translations[original]) element.setAttribute(attribute, translations[original]);
            else if (!english) element.setAttribute(attribute, original);
        }
    });
    const langButton = document.querySelector('[data-majra-language]');
    if (langButton) langButton.textContent = english ? 'العربية' : 'English';
};

const root = document.documentElement;
root.dataset.theme = localStorage.getItem('majra-theme') || 'dark';
const controls = document.createElement('div');
controls.className = 'majra-preferences';
controls.innerHTML = '<button type="button" data-majra-language>English</button><button type="button" data-majra-theme aria-label="Toggle color theme">☼</button>';
document.body.append(controls);
controls.querySelector('[data-majra-language]').addEventListener('click', () => {
    localStorage.setItem('majra-language', language() === 'en' ? 'ar' : 'en');
    applyLanguage();
});
controls.querySelector('[data-majra-theme]').addEventListener('click', () => {
    const theme = root.dataset.theme === 'light' ? 'dark' : 'light';
    root.dataset.theme = theme;
    localStorage.setItem('majra-theme', theme);
});
applyLanguage();
new MutationObserver(applyLanguage).observe(document.body, { childList: true, subtree: true });
