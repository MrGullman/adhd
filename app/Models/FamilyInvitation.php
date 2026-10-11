<?php

namespace App\Models;

use App\Enums\FamilyRole;
use App\Models\Concerns\BelongsToFamily;
use App\Models\Scopes\FamilyScope;
use Database\Factories\FamilyInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An invitation for another adult to join a family.
 *
 * @property int $id
 * @property int $family_id
 * @property string $email
 * @property FamilyRole $role
 * @property string $token_hash
 * @property int|null $invited_by
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['email', 'role', 'invited_by', 'expires_at'])]
#[Hidden(['token_hash'])]
class FamilyInvitation extends Model
{
    /** @use HasFactory<FamilyInvitationFactory> */
    use BelongsToFamily, HasFactory;

    public const VALID_DAYS = 7;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => FamilyRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Create an invitation in the current family and return it with its plain token.
     * An earlier pending invitation to the same address is replaced.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(string $email, FamilyRole $role, User $inviter): array
    {
        $email = Str::lower($email);

        self::query()->where('email', $email)->whereNull('accepted_at')->delete();

        $token = Str::random(64);

        $invitation = new self([
            'email' => $email,
            'role' => $role,
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(self::VALID_DAYS),
        ]);
        $invitation->token_hash = hash('sha256', $token);
        $invitation->save();

        return [$invitation, $token];
    }

    /**
     * Find a pending, unexpired invitation by its plain token.
     *
     * The person accepting is not a member yet, so the family scope is lifted for this lookup.
     */
    public static function findPendingByToken(string $token): ?self
    {
        return self::withoutGlobalScope(FamilyScope::class)
            ->with('family', 'inviter')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
