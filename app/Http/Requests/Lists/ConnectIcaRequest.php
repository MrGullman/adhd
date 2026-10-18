<?php

namespace App\Http\Requests\Lists;

use App\Models\IcaConnection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Logging in to ICA once. The personal ID and password are passed on to ICA
 * and forgotten: never saved, logged or flashed back (bootstrap/app.php).
 */
class ConnectIcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', IcaConnection::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'personal_id' => ['required', 'string', 'regex:/^(\d{2})?\d{6}-?\d{4}$/'],
            'ica_password' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['personal_id' => 'personnummer', 'ica_password' => 'lösenord'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['personal_id.regex' => 'Skriv personnumret som ÅÅMMDD-NNNN eller ÅÅÅÅMMDDNNNN.'];
    }

    /**
     * The personal ID as ICA wants it: twelve digits without a dash.
     */
    public function personalId(): string
    {
        $digits = preg_replace('/\D/', '', $this->string('personal_id')->toString()) ?? '';

        if (strlen($digits) === 10) {
            $year = (int) substr($digits, 0, 2);
            $digits = ($year > (int) now()->format('y') ? '19' : '20').$digits;
        }

        return $digits;
    }
}
