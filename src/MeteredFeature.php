<?php

namespace RenokiCo\CashierRegister;

class MeteredFeature extends Feature
{
    /**
     * The metered price ID, in case
     * the feature has a quota for metered price.
     *
     * @var string|int|null
     */
    protected $meteredId;

    /**
     * The metered price per unit, in case
     * the feature has a quota for metered price.
     *
     * @var float
     */
    protected $meteredPrice = 0.00;

    /**
     * Set the metered price.
     *
     * @param  string|int  $id
     * @param  float  $price
     * @return self
     */
    public function meteredPrice($id, float $price)
    {
        $this->meteredId = $id;
        $this->meteredPrice = $price;

        return $this;
    }

    /**
     * Get the metered price ID.
     *
     * @return string|int|null
     */
    public function getMeteredId()
    {
        return $this->meteredId;
    }

    /**
     * Get the metered price.
     *
     * @return float
     */
    public function getMeteredPrice(): float
    {
        return $this->meteredPrice;
    }

    /**
     * Check if this feature is resettable after each billing cycle.
     *
     * @return bool
     */
    public function isResettable(): bool
    {
        return true;
    }

    /**
     * Check if the feature has unlimited uses.
     *
     * @return bool
     */
    public function isUnlimited(): bool
    {
        return true;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $parent = parent::toArray();

        return [
            'id' => $parent['id'],
            'name' => $parent['name'],
            'description' => $parent['description'],
            'value' => $parent['value'],
            'metered_id' => $this->getMeteredId(),
            'metered_price' => $this->getMeteredPrice(),
        ];
    }
}
