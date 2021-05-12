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
     * The metered unit name.
     *
     * @var string
     */
    protected $meteredUnitName;

    /**
     * Set the metered price.
     *
     * @param  string|int  $id
     * @param  float  $price
     * @param  string|null  $unitName
     * @return self
     */
    public function meteredPrice($id, float $price, string $unitName = null)
    {
        $this->meteredId = $id;
        $this->meteredPrice = $price;
        $this->meteredUnitName = $unitName;

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
     * Get the metered unit name.
     *
     * @return string|null
     */
    public function getMeteredUnitName()
    {
        return $this->meteredUnitName;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'metered_id' => $this->getMeteredId(),
            'metered_price' => $this->getMeteredPrice(),
            'metered_unit_name' => $this->getMeteredUnitName(),
        ]);
    }
}
