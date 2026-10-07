<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Carbon|null $password_changed_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $role_id
 * @property int $failed_login_count
 * @property Carbon|null $locked_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /** FR-4: five consecutive failures lock the account. */
    public const LOCKOUT_THRESHOLD = 5;

    /** FR-4: the account stays locked for 15 minutes. */
    public const LOCKOUT_MINUTES = 15;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * FR-4: whether the account is inside its lockout window.
     */
    public function isLockedUntil(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * FR-4: count a failed credential attempt; lock the account for 15
     * minutes once 5 consecutive failures are reached.
     */
    public function recordFailedLogin(): void
    {
        $count = $this->failed_login_count + 1;

        $attributes = ['failed_login_count' => $count];

        if ($count >= self::LOCKOUT_THRESHOLD) {
            $attributes['locked_until'] = now()->addMinutes(self::LOCKOUT_MINUTES);
        }

        $this->forceFill($attributes)->save();
    }

    /**
     * FR-4: successful authentication ends the consecutive-failure streak.
     */
    public function resetFailedLogins(): void
    {
        if ($this->failed_login_count === 0 && $this->locked_until === null) {
            return;
        }

        $this->forceFill(['failed_login_count' => 0, 'locked_until' => null])->save();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'failed_login_count' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Determine whether the user must change their password before using the
     * application.
     */
    public function mustChangePassword(): bool
    {
        // FR-2: the column is NULL until the user's own successful change.
        return $this->password_changed_at === null;
    }
}
