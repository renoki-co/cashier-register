<?php

namespace RenokiCo\CashierRegister\Test\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RenokiCo\CashierRegister\BillableWithStripe;

class UserWithStripe extends Authenticatable
{
    use BillableWithStripe;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
