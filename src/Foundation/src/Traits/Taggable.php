<?php

namespace Redot\Traits;

trait Taggable
{
    /**
     * The "tags" attribute of the model.
     */
    protected static string $tagsAttribute = 'tags';

    /**
     * Scope a query to only include posts with the given tags.
     */
    public function scopeTagged($query, array|string|null $tags)
    {
        if (is_null($tags)) {
            return $query;
        }

        $tags = is_array($tags) ? $tags : [$tags];

        $query->where(function ($query) use ($tags) {
            foreach ($tags as $tag) {
                $query->orWhereJsonContains(static::$tagsAttribute, $tag);
            }
        });

        return $query;
    }

    /**
     * Get all tags from all posts.
     */
    public static function tags(): array
    {
        $attr = static::$tagsAttribute;
        $tags = static::whereNotNull($attr)->pluck($attr)->flatten()->unique()->toArray();

        return array_combine($tags, $tags);
    }

    /**
     * Attach a tag to the model.
     */
    public function attachTag(string ...$tags): void
    {
        $currentTags = $this->{static::$tagsAttribute} ?? [];
        $updatedTags = array_merge($currentTags, $tags);

        $this->syncTags(array_unique($updatedTags));
    }

    /**
     * Detach a tag from the model.
     */
    public function detachTag(string ...$tags): void
    {
        $currentTags = $this->{static::$tagsAttribute} ?? [];
        $updatedTags = array_diff($currentTags, $tags);

        $this->syncTags(array_values($updatedTags));
    }

    /**
     * Sync the tags of the model.
     */
    public function syncTags(?array $tags): void
    {
        $this->{static::$tagsAttribute} = $tags;
        $this->save();
    }
}
