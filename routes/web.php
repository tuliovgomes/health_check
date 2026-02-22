<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

use App\Http\Controllers\PricingController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\Integrations\IntegrationController;
use App\Http\Controllers\IntegrationsPageController;

Route::get('/plans', [PricingController::class, 'index'])->name('plans');
Route::post('/subscribe', [PricingController::class, 'subscribe'])->middleware('auth')->name('subscribe');

Route::middleware('auth')->group(function () {
    Route::get('/links', [LinkController::class, 'index'])->name('links.index');
    Route::get('/links/{link}', [LinkController::class, 'show'])->name('links.show');
    Route::post('/links', [LinkController::class, 'store'])->name('links.store');
    Route::delete('/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');

    Route::get('/integrations', [IntegrationsPageController::class, 'index'])->name('integrations.index');

    // Integration API routes
    Route::prefix('api')->group(function () {
        Route::get('/integrations', [IntegrationController::class, 'index'])->name('api.integrations.index');
        Route::post('/integrations', [IntegrationController::class, 'store'])->name('api.integrations.store');
        Route::get('/integrations/{integration}', [IntegrationController::class, 'show'])->name('api.integrations.show');
        Route::put('/integrations/{integration}', [IntegrationController::class, 'update'])->name('api.integrations.update');
        Route::delete('/integrations/{integration}', [IntegrationController::class, 'destroy'])->name('api.integrations.destroy');
        Route::post('/integrations/{integration}/test', [IntegrationController::class, 'test'])->name('api.integrations.test');
        
        // Link checks API routes
        Route::get('/links/{link}/checks', [DashboardController::class, 'getLinkChecks'])->name('api.links.checks');
    });
});
    