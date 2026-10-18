<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\IcaConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A family's connection to one of their ICA shopping lists (a trial an admin
 * turns on, FamilyFeatures::ICA). client and token are ICA's OAuth client and
 * tokens, encrypted. The ICA password is never stored.
 *
 * @property int $id
 * @property int $family_id
 * @property int|null $user_id
 * @property array<string, mixed> $client
 * @property array<string, mixed> $token
 * @property Carbon|null $token_expires_at
 * @property list<array{id: string, title: string}>|null $lists
 * @property string|null $list_id
 * @property string|null $list_title
 * @property Carbon|null $last_synced_at
 * @property string|null $last_error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class IcaConnection extends Model
{
    /** @use HasFactory<IcaConnectionFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * @var list<string>
     */
    protected $hidden = ['client', 'token'];

    /**
     * The parent who connected it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client' => 'encrypted:array',
            'token' => 'encrypted:array',
            'token_expires_at' => 'datetime',
            'lists' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
