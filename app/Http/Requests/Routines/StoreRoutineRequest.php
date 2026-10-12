<?php

namespace App\Http\Requests\Routines;

use App\Enums\DayPart;
use App\Enums\FamilyRole;
use App\Enums\StepType;
use App\Models\Routine;
use App\Rules\VisiblePicture;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoutineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Routine::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'picture_id' => ['nullable', 'integer', new VisiblePicture],
            'children' => ['array'],
            'children.*' => [
                'integer',
                'distinct',
                Rule::exists('family_user', 'user_id')
                    ->where('family_id', $current->id())
                    ->where('role', FamilyRole::Child->value),
            ],
            'day_part' => ['nullable', Rule::enum(DayPart::class)],
            'start_time' => ['nullable', 'date_format:H:i'],
            'done_by' => ['nullable', 'date_format:H:i', Rule::when($this->filled('start_time'), ['after:start_time'])],
            'weekdays' => ['array'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'ordered' => ['boolean'],
            'steps' => ['required', 'array', 'min:1', 'max:30'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.label' => ['required', 'string', 'max:50'],
            'steps.*.picture_id' => ['nullable', 'integer', new VisiblePicture],
            'steps.*.minutes' => ['nullable', 'integer', 'between:1,180'],
            'steps.*.type' => ['required', Rule::enum(StepType::class)],
            'steps.*.optional' => ['boolean'],
            'steps.*.weekdays' => ['array'],
            'steps.*.weekdays.*' => ['integer', 'between:1,7', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'namn',
            'picture_id' => 'bild',
            'children.*' => 'barn',
            'day_part' => 'del av dagen',
            'start_time' => 'börjar',
            'done_by' => 'klar senast',
            'weekdays' => 'veckodagar',
            'steps' => 'steg',
            'steps.*.label' => 'stegets text',
            'steps.*.minutes' => 'minuter',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'steps.required' => 'Lägg till minst ett steg.',
            'done_by.after' => '"Klar senast" måste vara efter starten.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function routine(): array
    {
        $weekdays = $this->normalizedWeekdays($this->input('weekdays'));

        return [
            'name' => $this->string('name')->toString(),
            'picture_id' => $this->filled('picture_id') ? $this->integer('picture_id') : null,
            'day_part' => $this->enum('day_part', DayPart::class),
            'start_time' => $this->filled('start_time') ? $this->string('start_time')->toString() : null,
            'done_by' => $this->filled('done_by') ? $this->string('done_by')->toString() : null,
            // Every day is stored as null.
            'weekdays' => count($weekdays) === 7 ? null : ($weekdays ?: null),
            'ordered' => $this->boolean('ordered', true),
        ];
    }

    /**
     * @return list<int>
     */
    public function children(): array
    {
        return array_values(array_map(intval(...), (array) $this->input('children', [])));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function steps(): array
    {
        $steps = [];

        foreach ((array) $this->input('steps', []) as $step) {
            $weekdays = $this->normalizedWeekdays($step['weekdays'] ?? []);

            $steps[] = [
                'id' => isset($step['id']) && $step['id'] !== '' ? (int) $step['id'] : null,
                'label' => (string) $step['label'],
                'picture_id' => isset($step['picture_id']) && $step['picture_id'] !== '' ? (int) $step['picture_id'] : null,
                'minutes' => isset($step['minutes']) && $step['minutes'] !== '' ? (int) $step['minutes'] : null,
                'type' => StepType::from((string) $step['type']),
                'optional' => filter_var($step['optional'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'weekdays' => count($weekdays) === 7 ? null : ($weekdays ?: null),
            ];
        }

        return $steps;
    }

    /**
     * @return list<int>
     */
    private function normalizedWeekdays(mixed $value): array
    {
        $days = array_values(array_unique(array_map(intval(...), is_array($value) ? $value : [])));
        sort($days);

        return $days;
    }
}
