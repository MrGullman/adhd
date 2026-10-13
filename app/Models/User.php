<?php

namespace App\Models;

use App\Enums\AvatarColor;
use App\Enums\FamilyRole;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
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
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * Adults log in with e-mail and password. Children have no e-mail or password
 * and log in with a code on a trusted device.
 *
 * @property int $id
 * @property int|null $active_family_id
 * @property string $name
 * @property string|null $community_name
 * @property AvatarColor $avatar_color
 * @property int|null $picture_id
 * @property bool $is_admin
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $pin
 * @property int $failed_pin_attempts
 * @property Carbon|null $login_disabled_at
 * @property bool $reminders_enabled
 * @property string|null $quiet_from
 * @property string|null $quiet_until
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $last_login_at
 * @property Carbon|null $suspended_at
 * @property string|null $suspended_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'avatar_color'])]
#[Hidden(['password', 'pin', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The same defaults as the database, so that a user just created has them too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'avatar_color' => 'teal',
        'reminders_enabled' => true,
        'quiet_from' => '20:00:00',
        'quiet_until' => '07:00:00',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'avatar_color' => AvatarColor::class,
            'is_admin' => 'boolean',
            'reminders_enabled' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'encrypted',
            'login_disabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
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
     * The avatar picture, e.g. a child's own photo. Without one the initial is shown.
     *
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    public function avatarUrl(): ?string
    {
        return $this->picture?->url();
    }

    /**
     * Only admins reach the admin portal, and only with a verified e-mail.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->email !== null && $this->email_verified_at !== null;
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
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Whether reminders wait at a time in Sweden, e.g. quiet 20:00–07:00.
     */
    public function isQuietAt(CarbonInterface $localTime): bool
    {
        if ($this->quiet_from === null || $this->quiet_until === null) {
            return false;
        }

        $time = $localTime->format('H:i');
        $from = substr($this->quiet_from, 0, 5);
        $until = substr($this->quiet_until, 0, 5);

        // Over midnight, e.g. 20:00–07:00.
        return $from <= $until
            ? $time >= $from && $time < $until
            : $time >= $from || $time < $until;
    }

    public function matchesPin(string $pin): bool
    {
        return $this->pin !== null && hash_equals($this->pin, $pin);
    }
}
