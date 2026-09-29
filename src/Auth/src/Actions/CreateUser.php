<?php

namespace Redot\Auth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Password;
use Redot\Auth\Contracts\CreatesUsers;
use Redot\Auth\Panel;

class CreateUser implements CreatesUsers
{
    /**
     * Get the validation rules for the registration request.
     */
    public function rules(Panel $panel): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . $panel->model()],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Create the user from the validated input.
     */
    public function create(array $input, Panel $panel): Model&Authenticatable
    {
        return $panel->model()::create(Arr::only($input, ['email', 'password']));
    }
}
