<?php

namespace RenokiCo\CashierRegister;

use Laravel\Cashier\Billable as CashierBillable;

trait Billable
{
    use CashierBillable;

    /**
     * {@inheritdoc}
     */
    public function subscriptions()
    {
        return $this->hasMany(Models\Subscription::class, $this->getForeignKey())
            ->orderBy('created_at', 'desc');
    }
}
