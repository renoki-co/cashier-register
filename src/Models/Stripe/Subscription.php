<?php

namespace RenokiCo\CashierRegister\Models\Stripe;

use Laravel\Cashier\Subscription as CashierSubscription;
use RenokiCo\CashierRegister\Concerns\HasPlans;
use RenokiCo\CashierRegister\Concerns\HasQuotas;

class Subscription extends CashierSubscription
{
    use HasPlans;
    use HasQuotas;

    /**
     * Get the service plan identifier for the resource.
     *
     * @return void
     */
    public function getPlanIdentifier()
    {
        return $this->stripe_plan;
    }
}
