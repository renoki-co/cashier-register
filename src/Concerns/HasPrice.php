<?php

namespace RenokiCo\CashierRegister\Concerns;

use RenokiCo\CashierRegister\Saas;

trait HasPrice
{
    /**
     * The instance price.
     *
     * @var float
     */
    protected $price = 0.00;

    /**
     * The currency of the instance.
     *
     * @var string
     */
    protected $currency = 'EUR';

    /**
     * Set the price for the instance.
     *
     * @param  float  $price
     * @param  string|null  $currency
     * @return self
     */
    public function price(float $price, string $currency = null)
    {
        $this->price = $price;
        $this->currency($currency);

        return $this;
    }

    /**
     * Set the currency for the plan.
     *
     * @param  string|null  $currency
     * @return self
     */
    public function currency(string $currency = null)
    {
        $this->currency = $currency ?: Saas::getCurrency($this->currency);

        return $this;
    }

    /**
     * Get the price of the instance.
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Get the currency of the instance.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}
