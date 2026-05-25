<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Model;

class RunActionPost extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    protected $casts = [
        'approved' => 'boolean',
    ];
}
