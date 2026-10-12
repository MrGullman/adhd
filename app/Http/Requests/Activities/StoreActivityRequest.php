<?php

namespace App\Http\Requests\Activities;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Rules\VisiblePicture;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Activity::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'name' => ['required', 'string', 'max:100'],
            'picture_id' => ['nullable', 'integer', new VisiblePicture],
            'participants' => ['array'],
            'participants.*' => [
                'integer',
                'distinct',
                Rule::exists('family_user', 'user_id')->where('family_id', $current->id()),
            ],
            'repeat' => ['required', 'in:once,weekly'],
            'date' => ['nullable', 'required_if:repeat,once', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'weekday' => ['nullable', 'required_if:repeat,weekly', 'integer', 'between:1,7'],
            'time' => ['nullable', 'date_format:H:i'],
            'visible_to_children' => ['boolean'],
            'unusual' => ['boolean'],
            'items' => ['array', 'max:30'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.label' => ['required', 'string', 'max:50'],
            'items.*.picture_id' => ['nullable', 'integer', new VisiblePicture],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'typ',
            'name' => 'namn',
            'picture_id' => 'bild',
            'participants.*' => 'vem',
            'date' => 'dag',
            'end_date' => 'sista dagen',
            'weekday' => 'veckodag',
            'time' => 'klockslag',
            'items.*.label' => 'saken',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required_if' => 'Välj vilken dag.',
            'weekday.required_if' => 'Välj veckodag.',
            'end_date.after_or_equal' => 'Sista dagen kan inte vara före första dagen.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activity(): array
    {
        $weekly = $this->input('repeat') === 'weekly';

        return [
            'type' => $this->enum('type', ActivityType::class),
            'name' => $this->string('name')->toString(),
            'picture_id' => $this->filled('picture_id') ? $this->integer('picture_id') : null,
            'date' => $this->filled('date') ? $this->string('date')->toString() : null,
            'end_date' => $this->filled('end_date') ? $this->string('end_date')->toString() : null,
            'weekday' => $weekly ? $this->integer('weekday') : null,
            'time' => $this->filled('time') ? $this->string('time')->toString() : null,
            'visible_to_children' => $this->boolean('visible_to_children'),
            'unusual' => $this->boolean('unusual'),
        ];
    }

    /**
     * @return list<int>
     */
    public function participants(): array
    {
        return array_values(array_map(intval(...), (array) $this->input('participants', [])));
    }

    /**
     * The packing list. Only activities have one.
     *
     * @return list<array{id: int|null, label: string, picture_id: int|null}>
     */
    public function items(): array
    {
        if ($this->enum('type', ActivityType::class) !== ActivityType::Activity) {
            return [];
        }

        $items = [];

        foreach ((array) $this->input('items', []) as $item) {
            $items[] = [
                'id' => isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null,
                'label' => (string) $item['label'],
                'picture_id' => isset($item['picture_id']) && $item['picture_id'] !== '' ? (int) $item['picture_id'] : null,
            ];
        }

        return $items;
    }
}
