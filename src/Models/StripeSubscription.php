<?php

namespace RenokiCo\CashierRegister\Models;

use Laravel\Cashier\Subscription as CashierSubscription;
use RenokiCo\CashierRegister\Saas;

class StripeSubscription extends CashierSubscription
{
    use Concerns\HasQuotas,
        Concerns\HasPlans;
}
