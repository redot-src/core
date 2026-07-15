<?php

namespace Redot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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
            static::forgetCachedValue($setting);
        });

        static::updated(function ($setting) {
            static::forgetCachedValue($setting);
        });

        static::deleted(function ($setting) {
            static::forgetCachedValue($setting);
        });
    }

    /**
     * Get the settings schema.
     */
    public static function schema(): array
    {
        return config('redot.settings', []);
    }

    /**
     * Get the settings defaults.
     */
    public static function defaults(): array
    {
        return collect(static::schema())
            ->filter(fn (array $definition): bool => array_key_exists('default', $definition))
            ->map(fn (array $definition): mixed => $definition['default'])
            ->all();
    }

    /**
     * Get the settings validation rules.
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (static::schema() as $key => $definition) {
            if (! array_key_exists('rules', $definition)) {
                continue;
            }

            if (array_is_list($definition['rules'])) {
                $rules[$key] = $definition['rules'];

                continue;
            }

            $rules = array_merge($rules, $definition['rules']);
        }

        return $rules;
    }

    /**
     * Get the default value for the specified setting key.
     */
    public static function default(string $key): mixed
    {
        if (config()->has("redot.settings.$key.default")) {
            return config("redot.settings.$key.default");
        }

        if (! str_contains($key, '.')) {
            return null;
        }

        [$settingKey, $jsonKey] = explode('.', $key, 2);

        return data_get(config("redot.settings.$settingKey.default"), $jsonKey);
    }

    /**
     * Forget cached setting values, including nested keys cached separately.
     */
    protected static function forgetCachedValue(self $setting): void
    {
        cache()->forget('settings.' . $setting->key);

        foreach (array_keys(Arr::dot(Arr::wrap(static::default($setting->key)))) as $key) {
            cache()->forget('settings.' . $setting->key . '.' . $key);
        }

        if (is_array($setting->value)) {
            foreach (array_keys(Arr::dot($setting->value)) as $key) {
                cache()->forget('settings.' . $setting->key . '.' . $key);
            }
        }
    }

    /**
     * Get the specified setting value.
     */
    public static function get(string $key, mixed $default = null, bool $fresh = false): mixed
    {
        if ($fresh) {
            cache()->forget('settings.' . $key);
        }

        $cacheKey = 'settings.' . $key;
        $value = cache()->get($cacheKey);

        if (! is_null($value)) {
            return $value;
        }

        $nestedSettingFound = false;

        // Handle nested settings
        if (str_contains($key, '.')) {
            [$settingKey, $jsonKey] = explode('.', $key, 2);

            $setting = static::where('key', $settingKey)->first();

            if ($setting) {
                $nestedSettingFound = true;
                $value = $setting->value;

                if (is_array($value) && array_key_exists($jsonKey, $value)) {
                    $value = $value[$jsonKey];
                } else {
                    $value = data_get($value, $jsonKey);
                }
            } else {
                $value = static::where('key', $key)->value('value');
            }
        } else {
            $value = static::where('key', $key)->value('value');
        }

        if (! is_null($value)) {
            cache()->forever($cacheKey, $value);

            return $value;
        }

        if ($nestedSettingFound) {
            return null;
        }

        return $default ?? static::default($key);
    }

    /**
     * Set the specified setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
