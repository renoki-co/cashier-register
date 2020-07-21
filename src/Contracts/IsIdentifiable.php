<?php

namespace RenokiCo\CashierRegister\Contracts;

trait IsIdentifiable
{
    /**
     * The display name of the instance.
     *
     * @var string
     */
    protected $name;

    /**
     * An unique id for the instance.
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
     * Set a name for the instance.
     *
     * @param  string  $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set an id for the instance.
     *
     * @param  string  $name
     * @return $this
     */
    public function id(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set a description for the instance.
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
     * Get the name of the instance.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the id of the instance.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the description of the instance.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }
}
