<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogPost extends Model
{
    protected $table = 'blog_posts';

    protected $guarded = [];

    public $timestamps = false;

    public function author(): BelongsTo
    {
        return $this->belongsTo(BlogAuthor::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'post_id');
    }
}
