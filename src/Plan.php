<?php

namespace RenokiCo\CashierRegister;

use Illuminate\Contracts\Support\Arrayable;

class Plan implements Arrayable
{
    use Concerns\HasFeatures,
        Concerns\HasPrice,
        Concerns\Deprecable,
        Concerns\IsIdentifiable,
        Concerns\HasData;

    /**
     * The yearly instance price.
     *
     * @var float
     */
    protected $yearlyPrice = 0.00;

    /**
     * Create a new plan builder.
     *
     * @param  string  $name
     * @param  string|int|null  $id
     * @param  string|int|null  $yearlyId
     * @return void
     */
    public function __construct(string $name, $id = null, $yearlyId = null)
    {
        $this->name($name);
        $this->id($id);
        $this->yearlyId($yearlyId);
        $this->features([]);
    }

    /**
     * Set the yearly ID for the plan.
     *
     * @param  string|int  $yearlyId
     * @return self
     */
    public function yearlyId($id)
    {
        $this->yearlyId = $id;

        return $this;
    }

    /**
     * Set the monthly price for the plan.
     *
     * @param  float  $price
     * @param  string|null  $currency
     * @return self
     */
    public function monthly(float $price, $currency = null)
    {
        return $this->price($price, $currency);
    }

    /**
     * Set the yearl price for the plan.
     *
     * @param  float  $price
     * @return self
     */
    public function yearly(float $price)
    {
        $this->yearlyPrice = $price;

        return $this;
    }

    /**
     * Get the yearly ID for the plan.
     *
     * @return string|int|null
     */
    public function getYearlyId()
    {
        return $this->yearlyId;
    }

    /**
     * Get the monthly price of the plan.
     *
     * @return float
     */
    public function getMonthlyPrice(): float
    {
        return $this->getPrice();
    }

    /**
     * Get the yearly price of the plan.
     *
     * @return float
     */
    public function getYearlyPrice(): float
    {
        return $this->yearlyPrice;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'yearlyId' => $this->getYearlyId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'monthly_price' => $this->getMonthlyPrice(),
            'yearly_price' => $this->getYearlyPrice(),
            'currency' => $this->getCurrency(),
            'active' => $this->isActive(),
            'features' => $this->getFeatures()->toArray(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
