<?php

namespace RenokiCo\CashierRegister\Test\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RenokiCo\CashierRegister\Billable;

class User extends Authenticatable
{
    use Billable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
