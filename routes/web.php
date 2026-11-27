<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\Citizen\ReportController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Company\CompanyPortalController;
use App\Http\Controllers\Company\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/safety', [PublicPageController::class, 'safety'])->name('safety');
Route::get('/pricing', [PublicPageController::class, 'pricing'])->name('pricing');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicPageController::class, 'storeContact'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'company' => redirect()->route('company.dashboard'),
        default => view('dashboard'),
    };
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:company'])->prefix('company')->name('company.')->group(function () {
    Route::get('/dashboard', [CompanyPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/areas', [CompanyPortalController::class, 'areas'])->name('areas');
    Route::post('/areas', [CompanyPortalController::class, 'storeArea'])->name('areas.store');
    Route::delete('/areas/{area}', [CompanyPortalController::class, 'destroyArea'])->name('areas.destroy');
    Route::get('/reports', [CompanyPortalController::class, 'reports'])->name('reports');
    Route::get('/subscription', [CompanyPortalController::class, 'subscription'])->name('subscription');
    Route::post('/checkout', [CheckoutController::class, 'start'])->name('checkout');
});
Route::view('/checkout', 'pages.checkout')->name('checkout');
Route::view('/payment/success', 'pages.payment-success')->name('payment.success');
Route::view('/payment/cancel', 'pages.payment-cancel')->name('payment.cancel');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/{section}', [AdminPageController::class, 'section'])->whereIn('section', AdminPageController::SECTIONS)->name('section');
});


Route::middleware('auth')->group(function () {
    Route::middleware('role:citizen')->group(function () {
        Route::get('/map', [ReportController::class, 'map'])->name('map');
        Route::get('/feed', [ReportController::class, 'index'])->name('feed');
        Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
        Route::post('/reports/{report:public_id}/confirm', [ReportController::class, 'confirm'])->name('reports.confirm');
    });
    Route::get('/reports/{report:public_id}', [ReportController::class, 'show'])->name('reports.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
