<?php

namespace RenokiCo\CashierRegister\Models\Paddle;

use Laravel\Paddle\Subscription as CashierSubscription;
use RenokiCo\CashierRegister\Concerns\HasPlans;
use RenokiCo\CashierRegister\Concerns\HasQuotas;

class Subscription extends CashierSubscription
{
    use HasPlans;
    use HasQuotas;

    /**
     * Get the service plan identifier for the resource.
     *
     * @return mixed
     */
    public function getPlanIdentifier()
    {
        return $this->paddle_plan;
    }
}
