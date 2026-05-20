<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class EmptyModel extends Model
{
    // Allow all attributes to be mass assigned.
    protected $guarded = [];
}
