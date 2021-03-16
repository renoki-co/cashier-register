<?php

namespace RenokiCo\CashierRegister\Test\Models\Paddle;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Paddle\Billable;

class User extends Authenticatable
{
    use Billable;

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
