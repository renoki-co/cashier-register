<?php

namespace RenokiCo\LaravelSaas\Test\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RenokiCo\LaravelSaas\Traits\HasSubscriptions;

class User extends Authenticatable
{
    use HasSubscriptions;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
