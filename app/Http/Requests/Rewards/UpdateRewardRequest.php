<?php

namespace App\Http\Requests\Rewards;

use App\Models\Reward;

class UpdateRewardRequest extends StoreRewardRequest
{
    public function authorize(): bool
    {
        $reward = $this->route('reward');

        return $reward instanceof Reward && ($this->user()?->can('update', $reward) ?? false);
    }
}
