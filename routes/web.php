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

// FR-1: server-side RBAC probe routes — DEV STUBS only, so the role
// middleware has observable coverage until the real modules replace them.
// Each group names the epic that owns the real routes.
Route::middleware(['auth', 'verified', 'role:EOS'])->group(function () {
    Route::get('/eos/attendance', fn () => 'eos ok')->name('dev.eos.attendance'); // epic 4
});

Route::middleware(['auth', 'verified', 'role:SUPERVISI,ADMINISTRATOR'])->group(function () {
    Route::get('/supervisi/attendance', fn () => 'supervisi ok')->name('dev.supervisi.attendance'); // epic 4
});

Route::middleware(['auth', 'verified', 'role:ADMINISTRATOR'])->group(function () {
    Route::get('/admin/users', fn () => 'admin ok')->name('dev.admin.users'); // epic 2.6
});

require __DIR__.'/settings.php';
