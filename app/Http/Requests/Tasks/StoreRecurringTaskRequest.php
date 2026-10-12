<?php

namespace App\Http\Requests\Tasks;

use App\Enums\DayPart;
use App\Enums\Frequency;
use App\Models\Family;
use App\Models\RecurringTask;
use App\Rules\VisiblePicture;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RecurringTask::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'picture_id' => ['nullable', 'integer', new VisiblePicture],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('family_user', 'user_id')->where('family_id', $current->id()),
            ],
            'frequency' => ['required', Rule::enum(Frequency::class)],
            'weekdays' => ['required_if:frequency,weekly', 'array'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'week_interval' => ['required_if:frequency,weekly', 'integer', 'in:1,2'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'day_part' => ['nullable', Rule::enum(DayPart::class)],
            'time' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'uppgift',
            'picture_id' => 'bild',
            'assigned_to' => 'vem',
            'frequency' => 'hur ofta',
            'weekdays' => 'veckodagar',
            'week_interval' => 'vecka',
            'starts_on' => 'första dagen',
            'ends_on' => 'sista dagen',
            'day_part' => 'del av dagen',
            'time' => 'klockslag',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weekdays.required_if' => 'Välj minst en veckodag.',
            'ends_on.after_or_equal' => 'Sista dagen kan inte vara före första dagen.',
        ];
    }

    /**
     * The validated recurring task. With a time, the part of the day follows
     * from the family's day parts.
     *
     * @return array<string, mixed>
     */
    public function recurringTask(Family $family): array
    {
        $time = $this->filled('time') ? $this->string('time')->toString() : null;
        $weekly = $this->enum('frequency', Frequency::class) === Frequency::Weekly;

        /** @var list<int|string> $weekdays */
        $weekdays = $weekly ? $this->array('weekdays') : [];
        $weekdays = array_map(intval(...), $weekdays);
        sort($weekdays);

        return [
            'title' => $this->string('title')->toString(),
            'picture_id' => $this->filled('picture_id') ? $this->integer('picture_id') : null,
            'assigned_to' => $this->filled('assigned_to') ? $this->integer('assigned_to') : null,
            'frequency' => $this->enum('frequency', Frequency::class),
            'weekdays' => $weekly ? $weekdays : null,
            'week_interval' => $weekly ? $this->integer('week_interval') : 1,
            'starts_on' => $this->string('starts_on')->toString(),
            'ends_on' => $this->filled('ends_on') ? $this->string('ends_on')->toString() : null,
            'day_part' => $time !== null
                ? $family->dayPartFor($time)
                : $this->enum('day_part', DayPart::class),
            'time' => $time,
        ];
    }
}
