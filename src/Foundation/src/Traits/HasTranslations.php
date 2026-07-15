<?php

namespace Redot\Traits;

use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    /**
     * @return array<string, mixed>
     */
    abstract public function attributesToArray();

    /**
     * @return array<string, mixed>
     */
    abstract public function relationsToArray();

    /**
     * Convert the model to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->attributesToArray();

        $translatables = array_filter($this->getTranslatableAttributes(), function ($key) use ($attributes) {
            return array_key_exists($key, $attributes);
        });

        foreach ($translatables as $field) {
            $attributes[$field] = $this->getTranslation($field, app()->getLocale());
        }

        return array_merge($attributes, $this->relationsToArray());
    }
}
