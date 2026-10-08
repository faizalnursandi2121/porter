<?php

use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

// 2.7: one consolidated matrix pinning FR-1 — every role reaches only its
// authorized features. Complements (not duplicates) the per-story suites:
// RoleAccessTest (middleware), UserManagementTest (2.6 page), AccountLockoutTest
// (FR-4), ForcePasswordChangeTest (2.3), PasswordResetEdgeTest (2.4).

$guardedRoutes = [
    '/eos/attendance' => [Role::EOS],
    '/supervisi/attendance' => [Role::SUPERVISI, Role::ADMINISTRATOR],
    '/admin/users' => [Role::SUPERVISI, Role::ADMINISTRATOR], // GET; POST is ADMIN-only
];

test('guests are redirected to login from every guarded route', function () use ($guardedRoutes) {
    foreach (array_keys($guardedRoutes) as $route) {
        $this->get($route)->assertRedirect(route('login', absolute: false));
    }
});

test('each role reaches exactly its authorized routes', function () use ($guardedRoutes) {
    $matrix = [
        Role::EOS => ['/eos/attendance'],
        Role::SUPERVISI => ['/supervisi/attendance', '/admin/users'],
        Role::HR => [],
        Role::ADMINISTRATOR => ['/supervisi/attendance', '/admin/users'],
    ];

    foreach ($matrix as $roleCode => $allowedRoutes) {
        $user = User::factory()->withRole($roleCode)->create();

        foreach ($guardedRoutes as $route => $authorizedRoles) {
            $response = $this->actingAs($user)->get($route);

            if (in_array($route, $allowedRoutes, true)) {
                $response->assertOk();
            } else {
                $response->assertForbidden();
            }
        }
    }
});

test('user creation writes stay administrator-only while supervisors read', function () {
    $supervisi = User::factory()->withRole(Role::SUPERVISI)->create();
    $administrator = User::factory()->withRole(Role::ADMINISTRATOR)->create();
    $payload = ['name' => 'X', 'email' => 'x@porter.test', 'role_code' => Role::HR];

    $this->actingAs($supervisi)->post('/admin/users', $payload)->assertForbidden();
    $this->actingAs($administrator)->post('/admin/users', $payload)
        ->assertRedirect();
});

test('site policy pins writes to administrators', function () {
    $site = Site::factory()->create();

    $cases = [
        Role::EOS => false,
        Role::SUPERVISI => false,
        Role::HR => false,
        Role::ADMINISTRATOR => true,
    ];

    foreach ($cases as $roleCode => $mayWrite) {
        $user = User::factory()->withRole($roleCode)->create();

        expect($user->can('viewAny', Site::class))->toBeTrue()
            ->and($user->can('view', $site))->toBeTrue()
            ->and($user->can('create', Site::class))->toBe($mayWrite)
            ->and($user->can('update', $site))->toBe($mayWrite)
            ->and($user->can('delete', $site))->toBe($mayWrite);
    }
});

test('forced password change applies to every role before any page', function () {
    foreach ([Role::EOS, Role::SUPERVISI, Role::HR, Role::ADMINISTRATOR] as $roleCode) {
        // withTemporaryPassword leaves password_changed_at NULL → the gate engages.
        $user = User::factory()->withRole($roleCode)->withTemporaryPassword()->create();

        $this->actingAs($user)
            ->get($roleCode === Role::SUPERVISI || $roleCode === Role::ADMINISTRATOR ? '/admin/users' : '/eos/attendance')
            ->assertRedirect(route('password.change.show', absolute: false));
    }
});

test('lockout is role-agnostic — any account locks after five failures', function () {
    foreach ([Role::EOS, Role::SUPERVISI, Role::HR, Role::ADMINISTRATOR] as $roleCode) {
        $user = User::factory()->withRole($roleCode)->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-'.$attempt,
            ]);
        }

        // Correct credentials inside the lockout window still fail.
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        expect($user->refresh()->isLockedUntil())->toBeTrue();
    }
});

test('no unguarded write route leaks into the web stack', function () {
    // Pin the auth middleware presence on every non-auth POST route except the
    // intentional public/benign set: password self-service, session-free
    // verification resend, passkey ceremony endpoints, logout, and health.
    $publicUris = ['login', 'forgot-password', 'reset-password', 'up', '/'];

    $unguarded = collect(Route::getRoutes())
        ->filter(fn ($route) => in_array('POST', $route->methods(), true))
        ->reject(fn ($route) => str_starts_with($route->uri, 'password/')
            || str_starts_with($route->uri, 'email/')
            || str_starts_with($route->uri, 'passkeys/')
            || str_starts_with($route->uri, 'user/passkeys')
            || in_array($route->uri, $publicUris, true)
            || str_contains($route->uri, 'logout')
            || str_contains($route->uri, 'two-factor')
            || str_contains($route->uri, 'confirm-password'))
        ->reject(fn ($route) => in_array('auth', (array) ($route->gatherMiddleware() ?? []), true)
            || in_array(Authenticate::class, (array) ($route->gatherMiddleware() ?? []), true));

    expect($unguarded->map->uri()->all())->toBe([]);
});
