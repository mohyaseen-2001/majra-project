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
        $expiredCompanyUser = User::updateOrCreate(
            ['email' => 'expired-company@majra.demo'],
            ['name' => 'مسؤول شركة منتهية (عرض تجريبي)', 'role' => 'company', 'password' => $demoPassword, 'email_verified_at' => now()],
        );

        $company = Company::updateOrCreate(
            ['user_id' => $companyUser->id],
            ['name' => 'شركة مَجرى للنقل (عرض تجريبي)', 'business_type' => 'logistics', 'status' => 'approved'],
        );
        $expiredCompany = Company::updateOrCreate(
            ['user_id' => $expiredCompanyUser->id],
            ['name' => 'شركة النقل المتكامل (عرض تجريبي)', 'registration_number' => 'JO-MAJRA-DEMO-02', 'business_type' => 'نقل ولوجستيات', 'status' => 'approved'],
        );

        CompanyArea::firstOrCreate(
            ['company_id' => $company->id, 'area_name' => 'عمّان - عرض تجريبي'],
            ['center_latitude' => 31.9539, 'center_longitude' => 35.9106, 'radius_km' => 12, 'alerts_enabled' => true],
        );

        $plans = [];
        foreach ([
            ['slug' => 'free', 'name' => 'مجانية', 'price' => 0, 'features' => ['الخريطة العامة', 'البلاغات العامة', 'دليل السلامة']],
            ['slug' => 'monitor', 'name' => 'مراقبة المناطق', 'price' => 19, 'features' => ['مناطق متابعة محفوظة', 'تنبيهات مخصصة', 'تقارير مخاطر']],
            ['slug' => 'operations', 'name' => 'العمليات', 'price' => 39, 'features' => ['كل مزايا مراقبة المناطق', 'سجل الإغلاقات', 'ملخصات تشغيلية']],
        ] as $plan) {
            $plans[$plan['slug']] = SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan + ['currency' => 'USD', 'interval' => 'month', 'is_active' => true]);
        }

        \App\Models\Subscription::firstOrCreate(
            ['company_id' => $company->id, 'subscription_plan_id' => $plans['monitor']->id],
            ['status' => 'active', 'starts_at' => now()->subDays(5), 'ends_at' => now()->addDays(25)],
        );
        \App\Models\Subscription::firstOrCreate(
            ['company_id' => $expiredCompany->id, 'subscription_plan_id' => $plans['operations']->id],
            ['status' => 'expired', 'starts_at' => now()->subMonths(2), 'ends_at' => now()->subMonth()],
        );

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

        foreach ([
            ['title' => '[عرض تجريبي] تجمع مياه قرب دوار المدينة الرياضية', 'description' => 'مثال تدريبي لبلاغ تجمع مياه على طريق رئيسي بعد مطر غزير.', 'hazard_type' => 'waterlogging', 'risk_level' => 'high', 'status' => 'pending', 'area_name' => 'عمّان - المدينة الرياضية', 'latitude' => 31.9957, 'longitude' => 35.8991],
            ['title' => '[عرض تجريبي] ارتفاع منسوب قرب جسر وادي السير', 'description' => 'مثال تدريبي لمتابعة مجرى مائي قرب طريق، ولا يصف خطراً حالياً.', 'hazard_type' => 'flood', 'risk_level' => 'medium', 'status' => 'approved', 'area_name' => 'عمّان - وادي السير', 'latitude' => 31.9556, 'longitude' => 35.8452],
            ['title' => '[عرض تجريبي] عائق مروري بعد أمطار إربد', 'description' => 'مثال تدريبي لعائق على طرف الطريق يحتاج مراجعة ميدانية.', 'hazard_type' => 'road_hazard', 'risk_level' => 'low', 'status' => 'resolved', 'area_name' => 'إربد - الوسط', 'latitude' => 32.5556, 'longitude' => 35.8500],
        ] as $report) {
            Report::firstOrCreate(
                ['user_id' => $citizen->id, 'title' => $report['title']],
                $report + ['public_id' => (string) str()->uuid(), 'source' => 'demo', 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'resolved_at' => $report['status'] === 'resolved' ? now() : null, 'expires_at' => now()->addDays(2)],
            );
        }

        Alert::firstOrCreate(
            ['title' => '[عرض تجريبي] تنبيه توضيحي'],
            ['created_by' => $admin->id, 'message' => 'هذا التنبيه تجريبي للعرض الأكاديمي ولا يمثل حالة طقس فعلية.', 'severity' => 'info', 'area_name' => 'عمّان - عرض تجريبي', 'source' => 'demo', 'is_active' => true, 'published_at' => now()],
        );
        Alert::firstOrCreate(
            ['title' => '[عرض تجريبي] متابعة مجرى مائي - وادي السير'],
            ['created_by' => $admin->id, 'message' => 'تنبيه توضيحي لاختبار عرض شدة التنبيه والمنطقة؛ لا يمثل خطراً حقيقياً.', 'severity' => 'warning', 'area_name' => 'عمّان - وادي السير', 'source' => 'demo', 'is_active' => true, 'published_at' => now(), 'expires_at' => now()->addDays(3)],
        );

        RoadClosure::firstOrCreate(
            ['title' => '[عرض تجريبي] إغلاق توضيحي للطريق'],
            ['created_by' => $admin->id, 'description' => 'بيانات تدريبية غير مرتبطة بإغلاق فعلي.', 'area_name' => 'عمّان - عرض تجريبي', 'status' => 'demo', 'latitude' => 31.9539, 'longitude' => 35.9106, 'diversion' => 'اتبع اللوحات الرسمية في الحالات الحقيقية.'],
        );
        RoadClosure::firstOrCreate(
            ['title' => '[عرض تجريبي] تحويلة تدريبية قرب المدينة الرياضية'],
            ['created_by' => $admin->id, 'description' => 'مثال لإظهار بيانات التحويلة ضمن الخريطة ولوحة الإدارة.', 'area_name' => 'عمّان - المدينة الرياضية', 'status' => 'demo', 'latitude' => 31.9957, 'longitude' => 35.8991, 'diversion' => 'استخدم طريقاً بديلاً وفق الإشارات الرسمية في الواقع.'],
        );
    }
}
