<?php

namespace Redot\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'language_id',
        'key',
        'value',
        'original_translation',
        'from_json',
        'is_published',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string>
     */
    protected $casts = [
        'from_json' => 'boolean',
        'is_published' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::updating(function (self $token) {
            if ($token->isDirty('value')) {
                $token->is_published = false;
            }
        });
    }

    /**
     * Get the language that owns the token.
     */
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Scope a query to only include published tokens.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include unpublished tokens.
     */
    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Scope a query to only include modified tokens.
     */
    public function scopeModified($query)
    {
        return $query->whereRaw('value != original_translation');
    }

    /**
     * Scope a query to only include tokens that are not modified.
     */
    public function scopeNotModified($query)
    {
        return $query->whereRaw('value = original_translation');
    }

    /**
     * Scope a query to only include tokens that are from JSON.
     */
    public function scopeFromJson($query)
    {
        return $query->where('from_json', true);
    }

    /**
     * Scope a query to only include tokens that are not from JSON.
     */
    public function scopeNotFromJson($query)
    {
        return $query->where('from_json', false);
    }
}
