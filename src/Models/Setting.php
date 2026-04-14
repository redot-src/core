<?php

namespace Redot\Models;

use Illuminate\Database\Eloquent\Model;
use Redot\Casts\Union;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => Union::class,
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted()
    {
        static::created(function ($setting) {
            cache()->forget('settings.' . $setting->key);
        });

        static::updated(function ($setting) {
            cache()->forget('settings.' . $setting->key);
        });
    }

    /**
     * Get the specified setting value.
     */
    public static function get(string $key, mixed $default = null, bool $fresh = false): mixed
    {
        if ($fresh) {
            cache()->forget('settings.' . $key);
        }

        return cache()->rememberForever('settings.' . $key, function () use ($key, $default) {
            $default = $default ?? config('redot.default_settings.' . $key);

            // Handle nested settings
            if (str_contains($key, '.')) {
                [$settingKey, $jsonKey] = explode('.', $key, 2);

                $setting = static::where('key', $settingKey)->first();

                if ($setting) {
                    $value = $setting->value;

                    if (is_array($value) && array_key_exists($jsonKey, $value)) {
                        return $value[$jsonKey];
                    }

                    return data_get($value, $jsonKey);
                }
            }

            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    /**
     * Set the specified setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
