<?php

namespace App\Models;

use App\Enums\AvatarColor;
use App\Enums\FamilyRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Adults log in with e-mail and password. Children have no e-mail or password
 * and log in with a code on a trusted device.
 *
 * @property int $id
 * @property int|null $active_family_id
 * @property string $name
 * @property AvatarColor $avatar_color
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $pin
 * @property int $failed_pin_attempts
 * @property Carbon|null $login_disabled_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'avatar_color'])]
#[Hidden(['password', 'pin', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'avatar_color' => AvatarColor::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'encrypted',
            'login_disabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Family, $this, Membership>
     */
    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class)
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function activeFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'active_family_id');
    }

    /**
     * The user's role in the given family, or null when not a member.
     */
    public function roleIn(Family $family): ?FamilyRole
    {
        $membership = $this->families()->whereKey($family->id)->first()?->getRelation('pivot');

        return $membership instanceof Membership ? $membership->role : null;
    }

    /**
     * The role from the pivot when the user was loaded through a family relation.
     */
    public function memberRole(): ?FamilyRole
    {
        $pivot = $this->relationLoaded('pivot') ? $this->getRelation('pivot') : null;

        return $pivot instanceof Membership ? $pivot->role : null;
    }

    /**
     * Children have no e-mail address, so there is nothing to verify.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email === null || $this->email_verified_at !== null;
    }

    public function loginEnabled(): bool
    {
        return $this->login_disabled_at === null;
    }

    /**
     * Compare a child's code in constant time.
     */
    public function matchesPin(string $pin): bool
    {
        return $this->pin !== null && hash_equals($this->pin, $pin);
    }
}
