<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $paidAt = DB::table('payments')->selectRaw('MAX(paid_at)')
            ->whereColumn('subscription_id', 'subscriptions.id')->where('status', 'paid');

        DB::table('subscriptions')->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
            ->select('subscriptions.id', 'subscriptions.starts_at', 'subscription_plans.interval')
            ->selectSub($paidAt, 'paid_at')->where('subscriptions.status', 'active')->whereNull('subscriptions.ends_at')
            ->whereExists(DB::table('payments')->selectRaw('1')->whereColumn('subscription_id', 'subscriptions.id')
                ->where('status', 'paid')->whereNotNull('paid_at'))
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $start = Carbon::parse($subscription->paid_at);
                    $end = $subscription->interval === 'year' ? $start->copy()->addYearNoOverflow() : $start->copy()->addMonthNoOverflow();
                    DB::table('subscriptions')->where('id', $subscription->id)->whereNull('ends_at')
                        ->update(['starts_at' => $start, 'ends_at' => $end]);
                }
            }, 'subscriptions.id', 'id');
    }

    public function down(): void
    {
        // Preserve billing dates that have already been recorded.
    }
};
