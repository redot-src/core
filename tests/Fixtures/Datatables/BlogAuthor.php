<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogAuthor extends Model
{
    protected $table = 'blog_authors';

    protected $guarded = [];

    public $timestamps = false;

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }
}
