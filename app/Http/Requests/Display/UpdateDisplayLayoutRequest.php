<?php

namespace App\Http\Requests\Display;

use App\Models\DisplayWidget;
use App\Support\CurrentFamily;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDisplayLayoutRequest extends FormRequest
{
    public function authorize(CurrentFamily $current): bool
    {
        return $this->user()?->can('manageMembers', $current->get()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $columns = DisplayWidget::COLUMNS;
        $rows = DisplayWidget::ROWS;

        return [
            'widgets' => ['required', 'array'],
            'widgets.*.id' => ['required', 'integer'],
            'widgets.*.x' => ['required', 'integer', 'min:0', 'max:'.($columns - 1)],
            'widgets.*.y' => ['required', 'integer', 'min:0', 'max:'.($rows - 1)],
            'widgets.*.w' => ['required', 'integer', 'min:1', 'max:'.$columns],
            'widgets.*.h' => ['required', 'integer', 'min:1', 'max:'.$rows],
            'widgets.*' => [function (string $attribute, mixed $value, Closure $fail) use ($columns, $rows): void {
                if (is_array($value) && (((int) ($value['x'] ?? 0)) + ((int) ($value['w'] ?? 0)) > $columns || ((int) ($value['y'] ?? 0)) + ((int) ($value['h'] ?? 0)) > $rows)) {
                    $fail('Widgeten får inte gå utanför skärmen.');
                }
            }],
        ];
    }
}
