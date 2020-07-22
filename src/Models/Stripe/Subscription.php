<?php

namespace RenokiCo\CashierRegister\Models\Stripe;

use Laravel\Cashier\Subscription as CashierSubscription;
use RenokiCo\CashierRegister\Concerns\HasPlans;
use RenokiCo\CashierRegister\Concerns\HasQuotas;
use RenokiCo\CashierRegister\Saas;

class Subscription extends CashierSubscription
{
    use HasQuotas, HasPlans;
}
