<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Models\Subscription;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\View;

class AdminPageController extends Controller
{
    public const SECTIONS = ['reports', 'road-closures', 'alerts', 'users', 'companies', 'subscriptions', 'payments', 'contacts', 'subscription-plans'];

    public function dashboard(): View
    {
        $trend = Report::query()->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->orderBy('day')->get();

        return view('admin.dashboard', [
            'stats' => [
                'pending' => Report::whereIn('status', ['pending', 'ai_checked'])->count(),
                'approved' => Report::where('status', 'approved')->count(),
                'closures' => RoadClosure::where('status', 'active')->count(),
                'alerts' => Alert::where('is_active', true)->count(),
                'citizens' => User::where('role', 'citizen')->count(),
                'contacts' => Contact::where('status', 'new')->count(),
            ],
            'reports' => Report::with('user:id,name')->latest()->limit(7)->get(),
            'trend' => $trend,
            'pulse' => Report::whereIn('status', ['pending', 'ai_checked'])->where('created_at', '>=', now()->subHours(3))->count(),
            'services' => [
                ['قاعدة البيانات', 'متصلة', 'good'],
                ['Open-Meteo', $this->weatherStatus(), $this->weatherTone()],
                ['OpenStreetMap', 'بيانات المستشفيات عند الطلب', 'ready'],
                ['Gemini', config('services.gemini.key') ? 'المفتاح مهيأ' : 'غير مهيأ · المراجعة اليدوية متاحة', config('services.gemini.key') ? 'ready' : 'pending'],
                ['Stripe Test', StripeCheckoutService::configured() ? 'مهيأ للدفع التجريبي' : 'غير مهيأ · الدفع غير متاح', StripeCheckoutService::configured() ? 'ready' : 'pending'],
            ],
        ]);
    }

    private function weatherStatus(): string
    {
        $weather = Cache::get('majra:weather:31.95:35.91:last');
        if (! is_array($weather)) {
            return 'لم يُطلب بعد · يظهر عند فتح الخريطة';
        }

        return ($weather['available'] ?? false)
            ? 'آخر تحديث '.(isset($weather['updated_at']) ? substr((string) $weather['updated_at'], 0, 16) : 'متاح')
            : 'غير متاح حالياً · الخريطة تعرض حالة المصدر';
    }

    private function weatherTone(): string
    {
        $weather = Cache::get('majra:weather:31.95:35.91:last');

        return ! is_array($weather) ? 'ready' : (($weather['available'] ?? false) ? 'good' : 'pending');
    }

    public function section(string $section): View
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);
        $labels = [
            'reports' => 'إدارة البلاغات', 'road-closures' => 'إغلاقات الطرق', 'alerts' => 'التنبيهات',
            'users' => 'المواطنون', 'companies' => 'الشركات', 'subscriptions' => 'الاشتراكات',
            'payments' => 'المدفوعات', 'contacts' => 'رسائل التواصل', 'subscription-plans' => 'باقات الاشتراك',
        ];
        $fields = match ($section) {
            'road-closures' => [['title', 'العنوان', 'text', true], ['area_name', 'المنطقة', 'text', true], ['status', 'الحالة', 'closure-status', true], ['latitude', 'خط العرض', 'number', true], ['longitude', 'خط الطول', 'number', true], ['diversion', 'التحويلة', 'text', false]],
            'alerts' => [['title', 'عنوان التنبيه', 'text', true], ['message', 'الرسالة', 'textarea', true], ['severity', 'الخطورة', 'severity', true], ['area_name', 'المنطقة', 'text', false], ['source', 'المصدر', 'source', true], ['is_active', 'نشط', 'boolean', false]],
            'users' => [['name', 'الاسم', 'text', true], ['email', 'البريد الإلكتروني', 'email', true], ['role', 'الدور', 'role', true], ['company_name', 'اسم الشركة عند اختيار دور شركة', 'company-name', true], ['business_type', 'نشاط الشركة', 'company-business', false], ['password', 'كلمة المرور المؤقتة', 'password', true], ['registration_number', 'رقم التسجيل', 'company-registration', false], ['phone', 'هاتف الشركة', 'company-phone', false], ['website', 'موقع الشركة', 'company-website', false], ['is_suspended', 'موقوف', 'boolean', false]],
            'companies' => [['name', 'اسم الشركة', 'text', true], ['account_name', 'اسم حساب الشركة', 'text', true], ['email', 'بريد حساب الشركة', 'email', true], ['registration_number', 'رقم التسجيل', 'text', false], ['status', 'الحالة', 'company-status', true], ['business_type', 'النشاط', 'text', false], ['password', 'كلمة مرور الحساب المؤقتة', 'password', true], ['phone', 'الهاتف', 'text', false], ['website', 'الموقع الإلكتروني', 'url', false]],
            'subscriptions' => [['company_id', 'رقم الشركة', 'number', true], ['subscription_plan_id', 'رقم الباقة', 'number', true], ['status', 'الحالة', 'subscription-status', true], ['starts_at', 'يبدأ في', 'datetime-local', false], ['ends_at', 'ينتهي في', 'datetime-local', false]],
            'payments' => [['company_id', 'رقم الشركة', 'number', true], ['subscription_id', 'رقم الاشتراك', 'number', false], ['amount', 'المبلغ', 'number', true], ['currency', 'العملة', 'text', true], ['status', 'الحالة', 'payment-status', true], ['provider', 'المزوّد', 'provider', true]],
            'contacts' => [['name', 'الاسم', 'text', true], ['email', 'البريد الإلكتروني', 'email', true], ['subject', 'الموضوع', 'text', true], ['message', 'الرسالة', 'textarea', true], ['status', 'الحالة', 'contact-status', true]],
            'subscription-plans' => [['slug', 'المعرّف', 'text', true], ['name', 'اسم الباقة', 'text', true], ['description', 'الوصف', 'textarea', false], ['price', 'السعر', 'number', true], ['currency', 'العملة', 'text', true], ['interval', 'الدورة', 'interval', true], ['features', 'المزايا (سطر لكل ميزة)', 'lines', false], ['is_active', 'مفعّلة', 'boolean', false]],
            default => [],
        };

        $createLabels = [
            'road-closures' => '＋ إضافة إغلاق طريق',
            'alerts' => '＋ إضافة تنبيه',
            'users' => '＋ إضافة مواطن أو حساب شركة',
            'companies' => '＋ إضافة شركة وحسابها',
            'subscription-plans' => '＋ إضافة باقة',
        ];

        return view($section === 'reports' ? 'admin.reports' : 'admin.resource', [
            'section' => $section,
            'title' => $labels[$section],
            'fields' => $fields,
            'createLabel' => $createLabels[$section] ?? '＋ إضافة سجل',
        ]);
    }
}
