<?php

namespace Redot\Auth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Redot\Auth\Panel;

interface CreatesUsers
{
    /**
     * Get the validation rules for the registration request.
     */
    public function rules(Panel $panel): array;

    /**
     * Create the user from the validated input.
     */
    public function create(array $input, Panel $panel): Model&Authenticatable;
}
