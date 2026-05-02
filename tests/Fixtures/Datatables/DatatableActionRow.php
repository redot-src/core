<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Model;

class DatatableActionRow extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'id' => 5,
        'active' => true,
    ];
}
