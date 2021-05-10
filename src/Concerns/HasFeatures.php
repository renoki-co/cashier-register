<?php

namespace RenokiCo\CashierRegister\Concerns;

use RenokiCo\CashierRegister\Feature;

trait HasFeatures
{
    /**
     * The features list for the instance.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $features;

    /**
     * Attach features to the instance.
     *
     * @param  array  $features
     * @return self
     */
    public function features(array $features)
    {
        $this->features = collect($features)->unique(function (Feature $feature) {
            return $feature->getId();
        });

        return $this;
    }

    /**
     * Get the list of all features.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * Get a specific feature by id.
     *
     * @param  string|int  $id
     * @return \RenokiCo\CashierRegister\Feature|null
     */
    public function getFeature($id)
    {
        return $this->getFeatures()->filter(function (Feature $feature) use ($id) {
            return $feature->getId() === $id;
        })->first();
    }
}
