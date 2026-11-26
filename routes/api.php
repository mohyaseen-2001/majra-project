<?php

use App\Http\Controllers\Admin\AdminDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('{resource}', [AdminDataController::class, 'index'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::post('{resource}', [AdminDataController::class, 'store'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::get('{resource}/{record}', [AdminDataController::class, 'show'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::match(['put', 'patch'], '{resource}/{record}', [AdminDataController::class, 'update'])->whereIn('resource', AdminDataController::RESOURCES);
    Route::delete('{resource}/{record}', [AdminDataController::class, 'destroy'])->whereIn('resource', AdminDataController::RESOURCES);
});
