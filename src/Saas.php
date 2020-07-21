<?php

namespace RenokiCo\CashierRegister;

class Saas
{
    /**
     * The list of plans.
     *
     * @var array
     */
    protected static $plans = [];

    /**
     * The list of items with fixed price.
     *
     * @var array
     */
    protected static $items = [];

    /**
     * Start creating a new plan.
     *
     * @param  string  $name
     * @param  string  $id
     * @return \RenokiCo\CashierRegister\Plan
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
     * @return \RenokiCo\CashierRegister\Feature
     */
    public static function feature(string $name, string $id, int $value = 0)
    {
        return new Feature($name, $id, $value);
    }

    /**
     * Assign a new item to the list.
     *
     * @param  string  $id
     * @param  string  $name
     * @param  float  $price
     * @param  string  $currency
     * @return $this
     */
    public static function item(string $id, string $name, float $price = 0.00, string $currency = 'EUR')
    {
        $item = new Item($id, $name, $price, $currency);

        static::$items[] = $item;

        return $item;
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
     * Get the list of items.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getItems()
    {
        return collect(static::$items);
    }

    /**
     * Get a specific item by id.
     *
     * @param  string  $id
     * @return Item|null
     */
    public static function getItem(string $id)
    {
        return collect(static::$items)->filter(function (Item $item) use ($id) {
            return $item->getId() === $id;
        })->first();
    }

    /**
     * Clear the plans.
     *
     * @return void
     */
    public static function clearPlans(): void
    {
        static::$plans = collect([]);
    }

    /**
     * Clear the plans.
     *
     * @return void
     */
    public static function clearItems(): void
    {
        static::$items = collect([]);
    }
}
