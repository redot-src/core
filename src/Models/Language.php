<?php

namespace Redot\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_rtl',
    ];

    /**
     * Get the tokens for the language.
     */
    public function tokens()
    {
        return $this->hasMany(LanguageToken::class);
    }

    /**
     * Get the current language.
     */
    public static function current(): self
    {
        return self::where('code', app()->getLocale())->first();
    }

    /**
     * Get the direction attribute.
     */
    public function getDirectionAttribute(): string
    {
        return $this->is_rtl ? 'rtl' : 'ltr';
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
