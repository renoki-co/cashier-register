<?php

namespace RenokiCo\CashierRegister\Test\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RenokiCo\CashierRegister\BillableWithPaddle;

class UserWithPaddle extends Authenticatable
{
    use BillableWithPaddle;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
