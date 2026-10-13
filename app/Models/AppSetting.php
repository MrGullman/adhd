<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * A setting for the whole app that admins change in the admin portal.
 *
 * @property string $key
 * @property mixed $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AppSetting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("app-setting:{$key}", fn () => static::query()->find($key)?->value);

        return $value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $setting = static::query()->findOrNew($key);
        $setting->forceFill(['key' => $key, 'value' => $value])->save();

        Cache::forget("app-setting:{$key}");
    }
}
