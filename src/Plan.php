<?php

namespace RenokiCo\CashierRegister;

use Illuminate\Contracts\Support\Arrayable;

class Plan implements Arrayable
{
    use Contracts\HasFeatures,
        Contracts\HasPrice,
        Contracts\Deprecable,
        Contracts\IsIdentifiable;

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
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'price' => $this->getPrice(),
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
