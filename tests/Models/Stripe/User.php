<?php

namespace RenokiCo\CashierRegister\Test\Models\Stripe;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RenokiCo\CashierRegister\BillableWithStripe;

class User extends Authenticatable
{
    use BillableWithStripe;

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
