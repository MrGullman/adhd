<?php

namespace App\Http\Requests\Settings;

use App\Enums\AvatarGroup;
use App\Enums\PicturePurpose;
use App\Models\Picture;
use App\Support\CurrentFamily;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * An adult's own profile picture: an avatar in the adult style, or one of
 * the family's own photos. Never a child's avatar or another family's photo.
 */
class UpdateProfilePictureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'picture_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) use ($current): void {
                    // Shared pictures and the current family's own (SharedOrFamilyScope).
                    $picture = Picture::query()->find((int) $value);

                    $allowed = $picture instanceof Picture && (
                        ($picture->family_id === null && $picture->avatar_group === AvatarGroup::Adult)
                        || ($picture->family_id !== null && $picture->family_id === $current->id()
                            && $picture->avatar_group === null && $picture->purpose === PicturePurpose::Support)
                    );

                    if (! $allowed) {
                        $fail('Välj en avatar eller ett eget foto.');
                    }
                },
            ],
        ];
    }
}
