<?php

namespace RenokiCo\CashierRegister;

class Item
{
    use Contracts\HasPrice,
        Contracts\IsIdentifiable;

    /**
     * Get the list of subitems for this item.
     *
     * @var array
     */
    protected $subitems = [];

    /**
     * Create a new item.
     *
     * @param  string  $id
     * @param  string  $name
     * @param  float  $price
     * @param  string  $currency
     * @return void
     */
    public function __construct(string $name, string $id, float $price = 0.00, string $currency = 'EUR')
    {
        $this->name = $name;
        $this->id = $id;
        $this->price = $price;
        $this->currency = $currency;
        $this->subitems = collect([]);
    }

    /**
     * Attach subitems to the item.
     *
     * @param  array  $subitems
     * @return $this
     */
    public function subitems(array $subitems)
    {
        $this->subitems = collect($subitems)
            ->unique(function (self $item) {
                return $item->getId();
            });

        return $this;
    }

    /**
     * Get the list of subitems.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSubitems()
    {
        return collect($this->subitems);
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
            'price' => $this->getPrice(),
            'currency' => $this->getCurrency(),
            'subitems' => $this->getSubitems(),
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
