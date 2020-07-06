<?php

namespace RenokiCo\Fuel;

class Saas
{
    /**
     * The list of plans.
     *
     * @var array
     */
    protected static $plans = [];

    /**
     * Start creating a new plan.
     *
     * @param  string  $name
     * @param  string  $id
     * @return \RenokiCo\Fuel\Plan
     */
    public static function plan(string $name, string $id)
    {
        $plan = new Plan($name, $id);

        static::$plans[] = $plan;

        return $plan;
    }

    /**
     * Start creating a new feature.
     *
     * @param  string  $name
     * @param  string  $id
     * @param  int  $value
     * @return \RenokiCo\Fuel\Feature
     */
    public static function feature(string $name, string $id, int $value = 0)
    {
        return new Feature($name, $id, $value);
    }

    /**
     * Get the list of plans.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getPlans()
    {
        return collect(static::$plans);
    }

    /**
     * Get the available plans.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAvailablePlans()
    {
        return static::getPlans()
            ->filter
            ->isActive();
    }

    /**
     * Get a specific plan by id.
     *
     * @param  string  $id
     * @return Plan|null
     */
    public static function getPlan(string $id)
    {
        return collect(static::$plans)->filter(function (Plan $plan) use ($id) {
            return $plan->getId() === $id;
        })->first();
    }

    /**
     * Clear the plans.
     *
     * @return void
     */
    public static function clearPlans(): void
    {
        static::$plans = [];
    }
}
