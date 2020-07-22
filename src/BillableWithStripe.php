<?php

namespace RenokiCo\CashierRegister;

use Laravel\Cashier\Billable;

trait BillableWithStripe
{
    use Billable;

    /**
     * {@inheritdoc}
     */
    public function subscriptions()
    {
        return $this->hasMany(config('saas.cashier.models.subscription.stripe'), $this->getForeignKey())
            ->orderBy('created_at', 'desc');
    }
}
