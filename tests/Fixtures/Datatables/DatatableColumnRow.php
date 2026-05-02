<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Model;

class DatatableColumnRow extends Model
{
    protected $attributes = [
        'name' => '<strong>Taylor</strong>',
        'email' => 'taylor@example.com',
    ];
}
