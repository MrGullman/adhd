<?php

namespace App\Http\Requests\Tasks;

use App\Enums\DayPart;
use App\Models\Family;
use App\Models\Task;
use App\Support\LocalTime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A task a child in läget Ungdom adds for themselves: always for the child,
 * today or later.
 */
class StoreOwnTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createOwn', Task::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $today = LocalTime::today();

        return [
            'title' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date_format:Y-m-d', "after_or_equal:{$today}", 'before_or_equal:'.LocalTime::now()->addDays(30)->toDateString()],
            'time' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'uppgift', 'date' => 'dag', 'time' => 'klockslag'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'Välj idag eller en senare dag.',
            'date.before_or_equal' => 'Välj en dag inom en månad.',
        ];
    }

    /**
     * The validated task, for the child who adds it.
     *
     * @return array{title: string, assigned_to: int, created_by: int, date: string, day_part: DayPart|null, time: string|null}
     */
    public function task(Family $family): array
    {
        $time = $this->filled('time') ? $this->string('time')->toString() : null;
        $childId = (int) $this->user()?->id;

        return [
            'title' => $this->string('title')->toString(),
            'assigned_to' => $childId,
            'created_by' => $childId,
            'date' => $this->string('date')->toString(),
            'day_part' => $time === null ? null : $family->dayPartFor($time),
            'time' => $time,
        ];
    }
}
