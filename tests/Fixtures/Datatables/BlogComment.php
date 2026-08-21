<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogComment extends Model
{
    protected $table = 'blog_comments';

    protected $guarded = [];

    public $timestamps = false;

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'post_id');
    }
}
