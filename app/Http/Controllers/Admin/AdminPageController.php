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
        ]);
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
            'users' => [['name', 'الاسم', 'text', true], ['email', 'البريد الإلكتروني', 'email', true], ['password', 'كلمة المرور المؤقتة', 'password', true], ['role', 'الدور', 'role', true], ['is_suspended', 'موقوف', 'boolean', false]],
            'companies' => [['user_id', 'رقم حساب المستخدم', 'number', true], ['name', 'اسم الشركة', 'text', true], ['registration_number', 'رقم التسجيل', 'text', false], ['business_type', 'النشاط', 'text', false], ['status', 'الحالة', 'company-status', true]],
            'subscriptions' => [['company_id', 'رقم الشركة', 'number', true], ['subscription_plan_id', 'رقم الباقة', 'number', true], ['status', 'الحالة', 'subscription-status', true], ['starts_at', 'يبدأ في', 'datetime-local', false], ['ends_at', 'ينتهي في', 'datetime-local', false]],
            'payments' => [['company_id', 'رقم الشركة', 'number', true], ['subscription_id', 'رقم الاشتراك', 'number', false], ['amount', 'المبلغ', 'number', true], ['currency', 'العملة', 'text', true], ['status', 'الحالة', 'payment-status', true], ['provider', 'المزوّد', 'provider', true]],
            'contacts' => [['name', 'الاسم', 'text', true], ['email', 'البريد الإلكتروني', 'email', true], ['subject', 'الموضوع', 'text', true], ['message', 'الرسالة', 'textarea', true], ['status', 'الحالة', 'contact-status', true]],
            'subscription-plans' => [['slug', 'المعرّف', 'text', true], ['name', 'اسم الباقة', 'text', true], ['description', 'الوصف', 'textarea', false], ['price', 'السعر', 'number', true], ['currency', 'العملة', 'text', true], ['interval', 'الدورة', 'interval', true], ['features', 'المزايا (سطر لكل ميزة)', 'lines', false], ['is_active', 'مفعّلة', 'boolean', false]],
            default => [],
        };

        return view($section === 'reports' ? 'admin.reports' : 'admin.resource', [
            'section' => $section,
            'title' => $labels[$section],
            'fields' => $fields,
        ]);
    }
}
