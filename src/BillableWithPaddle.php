<?php

namespace RenokiCo\CashierRegister;

use Laravel\Paddle\Billable;

trait BillableWithPaddle
{
    use Billable;

    /**
     * {@inheritdoc}
     */
    public function subscriptions()
    {
        return $this->hasMany(config('saas.cashier.models.subscription.paddle'), $this->getForeignKey())
            ->orderBy('created_at', 'desc');
    }
}
