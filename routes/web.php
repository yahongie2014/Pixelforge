<?php

use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\PixelforgeWebhookController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Pixelforge Webhook (exempt from CSRF via bootstrap/app.php, rate-limited via Redis)
Route::post('/webhooks/pixelforge', PixelforgeWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.pixelforge');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('generations.index')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    // Generations Studio & History (Redis throttled)
    Route::get('/generations', [GenerationController::class, 'index'])->name('generations.index');
    Route::post('/generations', [GenerationController::class, 'store'])
        ->middleware('throttle:generations')
        ->name('generations.store');
    Route::get('/generations/estimate', [GenerationController::class, 'estimate'])
        ->middleware('throttle:estimates')
        ->name('generations.estimate');
    Route::get('/generations/{generation}/status', [GenerationController::class, 'status'])->name('generations.status');

    // Balance & Credits (Stretch Goal 2)
    Route::post('/credits/buy', [CreditController::class, 'buy'])->name('credits.buy');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Operator Admin Report (Stretch Goal 1)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/report', [AdminReportController::class, 'index'])->name('report');
    });
});

require __DIR__.'/auth.php';
