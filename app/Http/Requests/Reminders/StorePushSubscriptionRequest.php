<?php

namespace App\Http\Requests\Reminders;

use App\Support\PublicAddress;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use NotificationChannels\WebPush\PushSubscription;

/**
 * A device that takes push notifications. The address comes from the
 * browser's push service and must be a public https address, since the
 * server sends to it.
 */
class StorePushSubscriptionRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'endpoint' => [
                'required',
                'string',
                'max:'.PushSubscription::ENDPOINT_MAX_LENGTH,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! PublicAddress::isPublicHttpsUrl($value)) {
                        $fail('Enheten gick inte att koppla.');
                    }
                },
            ],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', Rule::in(['aesgcm', 'aes128gcm'])],
        ];
    }
}
