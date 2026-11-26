<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReliabilityTransaction;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReliabilityPointsService
{
    public function awardForApprovedReport(Report $report): void
    {
        if (! $report->user_id || $report->status !== 'approved' || $report->is_false_report) {
            return;
        }

        DB::transaction(function () use ($report) {
            $user = User::query()->whereKey($report->user_id)->lockForUpdate()->first();
            if (! $user || $user->is_suspended || $user->false_report_count >= 3) {
                return;
            }
            if (ReliabilityTransaction::query()->where('report_id', $report->id)->where('reason', 'report_approved')->exists()) {
                return;
            }

            $awards = [['reason' => 'report_approved', 'points' => 10, 'description' => 'بلاغ اعتمده فريق المراجعة']];
            if ($report->latitude !== null && $report->longitude !== null) {
                $awards[] = ['reason' => 'precise_location', 'points' => 2, 'description' => 'إرفاق موقع البلاغ'];
            }

            $analysis = $report->aiAnalyses()->latest()->first();
            $quality = (int) data_get($analysis?->observations, 'image_quality_score', 0);
            if ($analysis && ! $analysis->is_duplicate && $quality >= 70) {
                $awards[] = ['reason' => 'clear_photo', 'points' => 3, 'description' => 'صورة واضحة ساعدت على المراجعة'];
            }

            foreach ($awards as $award) {
                $report->reliabilityTransactions()->create([
                    'user_id' => $user->id,
                    'points' => $award['points'],
                    'reason' => $award['reason'],
                    'description' => $award['description'],
                ]);
            }

            $user->increment('reliability_points', array_sum(array_column($awards, 'points')));
            $user->refresh();

            $eligibleBadgeIds = Badge::query()
                ->where('points_required', '<=', $user->reliability_points)
                ->whereNotIn('id', $user->badges()->select('badges.id'))
                ->pluck('id');

            foreach ($eligibleBadgeIds as $badgeId) {
                $user->badges()->attach($badgeId, ['earned_at' => now()]);
            }
        });
    }

    public function flagFalseReport(Report $report): void
    {
        if ($report->user_id) {
            User::query()->whereKey($report->user_id)->increment('false_report_count');
        }
    }
}
