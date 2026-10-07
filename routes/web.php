<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// FR-2: forced password change — the gate in EnsurePasswordChanged only lets
// pending accounts reach this pair.
Route::middleware(['auth'])->group(function () {
    Route::get('change-password', [ChangePasswordController::class, 'show'])
        ->name('password.change.show');

    Route::put('change-password', [ChangePasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.change.store');
});

require __DIR__.'/settings.php';
