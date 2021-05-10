<?php

namespace RenokiCo\CashierRegister\Concerns;

use RenokiCo\CashierRegister\Feature;

trait HasFeatures
{
    /**
     * The features list for the instance.
     *
     * @var array|\Illuminate\Support\Collection
     */
    protected $features = [];

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
        })->toArray();

        return $this;
    }

    /**
     * Get the list of all features.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFeatures()
    {
        return collect($this->features);
    }

    /**
     * Get a specific feature by id.
     *
     * @param  string  $id
     * @return Feature|null
     */
    public function getFeature(string $id)
    {
        return $this->getFeatures()->filter(function (Feature $feature) use ($id) {
            return $feature->getId() === $id;
        })->first();
    }
}
