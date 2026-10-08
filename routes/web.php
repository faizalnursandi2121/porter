<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\SiteMasterController;
use App\Http\Controllers\UserManagementController;
use App\Models\Role;
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

// 2.6: account management. Supervisi reads the list; Administrator creates
// accounts and resets passwords (PRD matrix — write actions are admin-only).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->middleware('role:'.Role::SUPERVISI.','.Role::ADMINISTRATOR)
        ->name('admin.users.index');

    Route::middleware('role:'.Role::ADMINISTRATOR)->group(function () {
        Route::post('/admin/users', [UserManagementController::class, 'store'])
            ->name('admin.users.store');

        Route::post('/admin/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
            ->name('admin.users.reset-password');
    });
});

// 3.1/3.2: site master data (FR-34) — every role may browse (FR-1);
// write actions stay Administrator-only (SitePolicy enforces server-side).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/supervisi/master/sites', [SiteMasterController::class, 'index'])
        ->middleware('role:'.Role::SUPERVISI.','.Role::ADMINISTRATOR.','.Role::HR.','.Role::EOS)
        ->name('master.sites.index');

    Route::middleware('role:'.Role::ADMINISTRATOR)->group(function () {
        Route::post('/supervisi/master/sites', [SiteMasterController::class, 'store'])
            ->name('master.sites.store');

        Route::put('/supervisi/master/sites/{site}', [SiteMasterController::class, 'update'])
            ->name('master.sites.update');

        Route::post('/supervisi/master/sites/{site}/toggle-active', [SiteMasterController::class, 'toggleActive'])
            ->name('master.sites.toggle-active');
    });
});

require __DIR__.'/settings.php';
