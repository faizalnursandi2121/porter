<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ChangePasswordController extends Controller
{
    /**
     * Show the forced password change page.
     */
    public function show(): Response
    {
        return Inertia::render('auth/change-password', [
            'passwordRules' => Password::defaults()?->toPasswordRulesString(),
        ]);
    }

    /**
     * Store the new password.
     *
     * FR-2: any successful self-service change marks the temporary-password
     * state resolved, whatever endpoint served it.
     */
    public function store(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->password,
            'password_changed_at' => now(),
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Password changed.'),
        ]);

        return redirect()->route('dashboard');
    }
}
