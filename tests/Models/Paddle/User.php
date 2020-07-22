<?php

namespace RenokiCo\CashierRegister\Test\Models\Paddle;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RenokiCo\CashierRegister\BillableWithPaddle;

class User extends Authenticatable
{
    use BillableWithPaddle;

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
