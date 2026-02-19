<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

use App\Http\Controllers\PricingController;
use App\Http\Controllers\LinkController;
use App\Http\Middleware\EnsureWithinLinksQuota;

Route::get('/plans', [PricingController::class, 'index'])->name('plans');
Route::post('/subscribe', [PricingController::class, 'subscribe'])->middleware('auth')->name('subscribe');

Route::middleware('auth')->group(function () {
    Route::get('/links', [LinkController::class, 'index'])->name('links.index');
    Route::post('/links', [LinkController::class, 'store'])->middleware(EnsureWithinLinksQuota::class)->name('links.store');
    Route::delete('/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');
});
