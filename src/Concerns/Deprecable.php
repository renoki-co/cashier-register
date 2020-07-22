<?php

namespace RenokiCo\CashierRegister\Concerns;

trait Deprecable
{
    /**
     * Wether the instance is active.
     *
     * @var bool
     */
    protected $active = true;

    /**
     * Mark the instance as deprecated.
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
     * Check if the instance is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }
}
