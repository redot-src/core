<?php

namespace Redot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Redot\Casts\Union;
use Redot\Support\SettingDefinition;

class Setting extends Model
{
    /**
     * The registered setting definitions.
     *
     * @var array<string, SettingDefinition>
     */
    protected static array $definitions = [];

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
     * Define an application setting.
     */
    public static function define(string $key): SettingDefinition
    {
        return static::$definitions[$key] = new SettingDefinition;
    }

    /**
     * Get the settings schema.
     */
    public static function schema(): array
    {
        return array_map(
            fn (SettingDefinition $definition): array => $definition->toArray(),
            static::$definitions,
        );
    }

    /**
     * Get the settings defaults.
     */
    public static function defaults(): array
    {
        return collect(static::$definitions)
            ->filter(fn (SettingDefinition $definition): bool => $definition->hasDefault())
            ->map(fn (SettingDefinition $definition): mixed => $definition->getDefault())
            ->all();
    }

    /**
     * Get the settings validation rules.
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (static::$definitions as $key => $definition) {
            $validationRules = $definition->getRules();

            if ($validationRules === null) {
                continue;
            }

            if (is_string($validationRules) || array_is_list($validationRules)) {
                $rules[$key] = $validationRules;

                continue;
            }

            $rules = array_merge($rules, $validationRules);
        }

        return $rules;
    }

    /**
     * Get the default value for the specified setting key.
     */
    public static function default(string $key): mixed
    {
        if (isset(static::$definitions[$key]) && static::$definitions[$key]->hasDefault()) {
            return static::$definitions[$key]->getDefault();
        }

        if (! str_contains($key, '.')) {
            return null;
        }

        [$settingKey, $jsonKey] = explode('.', $key, 2);

        if (! isset(static::$definitions[$settingKey]) || ! static::$definitions[$settingKey]->hasDefault()) {
            return null;
        }

        return data_get(static::$definitions[$settingKey]->getDefault(), $jsonKey);
    }

    /**
     * Get the declared type for the specified setting key.
     */
    public static function type(string $key): ?string
    {
        if (isset(static::$definitions[$key])) {
            return static::$definitions[$key]->getType();
        }

        $settingKey = Str::before($key, '.');

        if (! isset(static::$definitions[$settingKey])) {
            return null;
        }

        return static::$definitions[$settingKey]->getType();
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
