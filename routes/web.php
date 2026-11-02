<?php

use App\Http\Controllers\CheckinController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShortLinkController;
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

// SMS short links.
Route::get('/s/{code}', [ShortLinkController::class, 'show'])
    ->middleware('throttle:120,1')
    ->name('short.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
