<?php

namespace RenokiCo\CashierRegister;

use Illuminate\Contracts\Support\{ Arrayable };

class Plan implements Arrayable
{
    /**
     * The display name of the plan.
     *
     * @var string
     */
    protected $name;

    /**
     * An unique id for the plan.
     *
     * @var string
     */
    protected $id;

    /**
     * Description for the plan.
     *
     * @var string
     */
    protected $description;

    /**
     * The plan price.
     *
     * @var float
     */
    protected $price = 0.00;

    /**
     * The currency of the price.
     *
     * @var string
     */
    protected $currency = 'EUR';

    /**
     * Wether the plan is active.
     *
     * @var bool
     */
    protected $active = true;

    /**
     * The features list for the plan.
     *
     * @var array|\Illuminate\Support\Collection
     */
    protected $features = [];

    /**
     * Create a new plan builder.
     *
     * @param  string  $name
     * @param  string  $id
     * @return void
     */
    public function __construct(string $name, string $id)
    {
        $this->name = $name;
        $this->id = $id;
        $this->features = collect([]);
    }

    /**
     * Set a description for the plan.
     *
     * @param  string  $description
     * @return $this
     */
    public function description(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Assign a price.
     *
     * @param  float  $price
     * @param  string  $currency
     * @return $this
     */
    public function price(float $price, string $currency = 'EUR')
    {
        $this->price = $price;
        $this->currency = $currency;

        return $this;
    }

    /**
     * Mark the plan as deprecated.
     *
     * @return $this
     */
    public function archive()
    {
        $this->active = false;

        return $this;
    }

    /**
     * Alias for archive().
     *
     * @return $this
     */
    public function deprecated()
    {
        return $this->archive();
    }

    /**
     * Attach features to the plan.
     *
     * @param  array  $features
     * @return $this
     */
    public function features(array $features)
    {
        $this->features = collect($features)
            ->filter(function (Feature $feature) {
                return $feature instanceof Feature;
            })
            ->unique(function (Feature $feature) {
                return $feature->getId();
            });

        return $this;
    }

    /**
     * Get the id of the plan.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Check if the plan is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
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

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'active' => $this->active,
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
