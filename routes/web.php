<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Citizen\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/about', 'pages.about')->name('about');
Route::view('/safety', 'pages.safety')->name('safety');
Route::view('/pricing', 'pages.pricing')->name('pricing');
Route::view('/contact', 'pages.contact')->name('contact');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::view('/company/dashboard', 'dashboard')
    ->middleware(['auth', 'role:company'])
    ->name('company.dashboard');

Route::view('/admin/dashboard', 'dashboard')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');


Route::middleware('auth')->group(function () {
    Route::middleware('role:citizen')->group(function () {
        Route::get('/map', [ReportController::class, 'map'])->name('map');
        Route::get('/feed', [ReportController::class, 'index'])->name('feed');
        Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    });
    Route::get('/reports/{report:public_id}', [ReportController::class, 'show'])->name('reports.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
