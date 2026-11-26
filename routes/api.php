<?php

use App\Http\Controllers\Admin\AdminDataController;
use App\Http\Controllers\PublicMapDataController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('weather', [PublicMapDataController::class, 'weather'])->middleware('throttle:30,1');
Route::get('hospitals', [PublicMapDataController::class, 'hospitals'])->middleware('throttle:10,1');
Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('{resource}', [AdminDataController::class, 'index'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::post('{resource}', [AdminDataController::class, 'store'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::get('{resource}/{record}', [AdminDataController::class, 'show'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::match(['put', 'patch'], '{resource}/{record}', [AdminDataController::class, 'update'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::delete('{resource}/{record}', [AdminDataController::class, 'destroy'])->whereIn('resource', AdminDataController::RESOURCES);
});
