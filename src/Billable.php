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
        return $this->hasMany(config('saas.cashier.models.subscription'), $this->getForeignKey())
            ->orderBy('created_at', 'desc');
    }
}
