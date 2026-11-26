<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Report;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reports:expire', function () {
    $count = Report::query()->whereIn('status', ['pending', 'ai_checked', 'approved'])
        ->whereNotNull('expires_at')->where('expires_at', '<=', now())->update(['status' => 'expired', 'updated_at' => now()]);
    $this->info("Expired {$count} stale reports.");
})->purpose('Expire reports past their review window');

Schedule::command('reports:expire')->hourly()->withoutOverlapping();
