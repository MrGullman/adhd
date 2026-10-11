<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Models\Concerns\BelongsToFamily;
use App\Models\Scopes\FamilyScope;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A trusted device: a shared family device, a child's own device or a home display.
 *
 * @property int $id
 * @property int $family_id
 * @property int|null $user_id
 * @property DeviceType $type
 * @property string $name
 * @property string $token_hash
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'name', 'user_id', 'settings'])]
#[Hidden(['token_hash'])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * The long-lived cookie that holds the device token.
     */
    public const COOKIE = 'npf_device';

    /**
     * How long the device cookie lives, in minutes (browsers cap cookies at about 400 days).
     */
    public const COOKIE_MINUTES = 60 * 24 * 400;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeviceType::class,
            'settings' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * The child a child device belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a device and return it with its plain token. Only the hash of the
     * token is stored. Without an explicit family the current family is used.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(DeviceType $type, string $name, ?User $user = null, ?Family $family = null): array
    {
        $token = Str::random(64);

        $device = new self(['type' => $type, 'name' => $name, 'user_id' => $user?->id]);
        $device->family_id = $family?->id;
        $device->token_hash = hash('sha256', $token);
        $device->save();

        return [$device, $token];
    }

    /**
     * Find a device by its plain token.
     *
     * This runs before the current family is known (the device decides the family),
     * so the family scope is lifted for this single lookup.
     */
    public static function findByToken(?string $token): ?self
    {
        if ($token === null || $token === '') {
            return null;
        }

        return self::withoutGlobalScope(FamilyScope::class)
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }
}
