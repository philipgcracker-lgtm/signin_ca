<?php

use App\Http\Controllers\CheckinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventQrController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ManualVisitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\VisitorExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public visitor check-in (no auth). Plain Blade, no Vite dependency.
Route::get('/e/{event:slug}', [CheckinController::class, 'show'])->name('checkin.show');
Route::post('/e/{event:slug}/checkin', [CheckinController::class, 'store'])
    ->middleware('throttle:checkin')
    ->name('checkin.store');
Route::get('/e/{event:slug}/thanks', [CheckinController::class, 'thanks'])->name('checkin.thanks');

// Post-event feedback (signed per-visitor links; see App\Services\FeedbackLink).
Route::middleware(['signed', 'throttle:30,1'])->whereNumber('visitor')->group(function () {
    Route::get('/f/{visitor}', [FeedbackController::class, 'show'])->name('feedback.show');
    Route::post('/f/{visitor}', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/f/{visitor}/thanks', [FeedbackController::class, 'thanks'])->name('feedback.thanks');
});

// SMS short links.
Route::get('/s/{code}', [ShortLinkController::class, 'show'])
    ->middleware('throttle:120,1')
    ->name('short.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('events.store');
    Route::get('/events/{event}', [DashboardController::class, 'show'])
        ->whereNumber('event')
        ->name('events.show');
    Route::get('/events/{event}/qr.svg', [EventQrController::class, 'svg'])
        ->middleware('throttle:60,1')
        ->whereNumber('event')
        ->name('events.qr');
    Route::get('/events/{event}/sign', [EventQrController::class, 'sign'])
        ->whereNumber('event')
        ->name('events.sign');
    Route::get('/events/{event}/visitors.csv', VisitorExportController::class)
        ->middleware('throttle:30,1')
        ->whereNumber('event')
        ->name('visitors.csv');
    Route::get('/events/{event}/visitors/create', [ManualVisitorController::class, 'create'])
        ->whereNumber('event')
        ->name('visitors.create');
    Route::post('/events/{event}/visitors', [ManualVisitorController::class, 'store'])
        ->middleware('throttle:60,1')
        ->whereNumber('event')
        ->name('visitors.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
