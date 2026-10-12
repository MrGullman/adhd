<?php

namespace App\Http\Requests\Tasks;

use App\Enums\DayPart;
use App\Models\Family;
use App\Models\Task;
use App\Rules\VisiblePicture;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Task::class) ?? false;
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
            'date' => ['required', 'date_format:Y-m-d'],
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
            'date' => 'dag',
            'day_part' => 'del av dagen',
            'time' => 'klockslag',
        ];
    }

    /**
     * The validated task. With a time, the part of the day follows from the
     * family's day parts.
     *
     * @return array{title: string, picture_id: int|null, assigned_to: int|null, date: string, day_part: DayPart|null, time: string|null}
     */
    public function task(Family $family): array
    {
        $time = $this->filled('time') ? $this->string('time')->toString() : null;

        return [
            'title' => $this->string('title')->toString(),
            'picture_id' => $this->filled('picture_id') ? $this->integer('picture_id') : null,
            'assigned_to' => $this->filled('assigned_to') ? $this->integer('assigned_to') : null,
            'date' => $this->string('date')->toString(),
            'day_part' => $time !== null
                ? $family->dayPartFor($time)
                : $this->enum('day_part', DayPart::class),
            'time' => $time,
        ];
    }
}
