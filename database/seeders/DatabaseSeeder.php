<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Alert;
use App\Models\Badge;
use App\Models\Company;
use App\Models\CompanyArea;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demoPassword = Hash::make('MajraDemo2026!');

        $admin = User::updateOrCreate(
            ['email' => 'admin@majra.demo'],
            ['name' => 'مدير مَجرى التجريبي', 'role' => 'admin', 'password' => $demoPassword, 'email_verified_at' => now()],
        );
        $citizen = User::updateOrCreate(
            ['email' => 'citizen@majra.demo'],
            ['name' => 'مواطن تجريبي', 'role' => 'citizen', 'password' => $demoPassword, 'email_verified_at' => now()],
        );
        $companyUser = User::updateOrCreate(
            ['email' => 'company@majra.demo'],
            ['name' => 'مستخدم الشركة التجريبية', 'role' => 'company', 'password' => $demoPassword, 'email_verified_at' => now()],
        );

        $company = Company::updateOrCreate(
            ['user_id' => $companyUser->id],
            ['name' => 'شركة مَجرى للنقل (عرض تجريبي)', 'business_type' => 'logistics', 'status' => 'approved'],
        );

        CompanyArea::firstOrCreate(
            ['company_id' => $company->id, 'area_name' => 'عمّان - عرض تجريبي'],
            ['center_latitude' => 31.9539, 'center_longitude' => 35.9106, 'radius_km' => 12, 'alerts_enabled' => true],
        );

        foreach ([
            ['slug' => 'free', 'name' => 'مجانية', 'price' => 0, 'features' => ['الخريطة العامة', 'البلاغات العامة', 'دليل السلامة']],
            ['slug' => 'monitor', 'name' => 'مراقبة المناطق', 'price' => 19, 'features' => ['مناطق متابعة محفوظة', 'تنبيهات مخصصة', 'تقارير مخاطر']],
            ['slug' => 'operations', 'name' => 'العمليات', 'price' => 39, 'features' => ['كل مزايا مراقبة المناطق', 'سجل الإغلاقات', 'ملخصات تشغيلية']],
        ] as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan + ['currency' => 'JOD', 'interval' => 'month', 'is_active' => true]);
        }

        foreach ([
            ['slug' => 'first-signal', 'name' => 'الإشارة الأولى', 'description' => 'أول بلاغ موثّق.', 'points_required' => 10, 'icon' => 'signal'],
            ['slug' => 'trusted-eye', 'name' => 'عين موثوقة', 'description' => 'مساهمات دقيقة ومتواصلة.', 'points_required' => 50, 'icon' => 'eye'],
            ['slug' => 'community-guardian', 'name' => 'حارس المجتمع', 'description' => 'مشاركة فعّالة في سلامة المجتمع.', 'points_required' => 150, 'icon' => 'shield'],
        ] as $badge) {
            Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }

        Report::firstOrCreate(
            ['user_id' => $citizen->id, 'title' => '[عرض تجريبي] بلاغ مياه قرب طريق رئيسي'],
            [
                'description' => 'بيانات توضيحية للعرض الأكاديمي فقط، وليست بلاغاً حقيقياً أو تنبيهاً ميدانياً.',
                'hazard_type' => 'flood', 'risk_level' => 'medium', 'status' => 'approved', 'source' => 'demo',
                'area_name' => 'عمّان - عرض تجريبي', 'latitude' => 31.9539, 'longitude' => 35.9106,
                'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'expires_at' => now()->addHours(24),
            ],
        );

        Alert::firstOrCreate(
            ['title' => '[عرض تجريبي] تنبيه توضيحي'],
            ['created_by' => $admin->id, 'message' => 'هذا التنبيه تجريبي للعرض الأكاديمي ولا يمثل حالة طقس فعلية.', 'severity' => 'info', 'area_name' => 'عمّان - عرض تجريبي', 'source' => 'demo', 'is_active' => true, 'published_at' => now()],
        );

        RoadClosure::firstOrCreate(
            ['title' => '[عرض تجريبي] إغلاق توضيحي للطريق'],
            ['created_by' => $admin->id, 'description' => 'بيانات تدريبية غير مرتبطة بإغلاق فعلي.', 'area_name' => 'عمّان - عرض تجريبي', 'status' => 'demo', 'latitude' => 31.9539, 'longitude' => 35.9106, 'diversion' => 'اتبع اللوحات الرسمية في الحالات الحقيقية.'],
        );
    }
}
