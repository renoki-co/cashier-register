<?php

namespace RenokiCo\Fuel\Models;

use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'saas_subscription_usages';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'subscription_id', 'feature_id',
        'used', 'valid_until',
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'valid_until',
    ];

    /**
     * The feature this usage belongs to.
     *
     * @return mixed
     */
    public function feature()
    {
        return $this->belongsTo(config('saas.models.feature'));
    }

    /**
     * The subscription this usage is for.
     *
     * @return mixed
     */
    public function subscription()
    {
        return $this->belongsTo(config('saas.models.subscription'));
    }

    /**
     * Check whether usage has been expired or not.
     *
     * @return bool
     */
    public function expired(): bool
    {
        if (is_null($this->valid_until)) {
            return false;
        }

        return now()->gte($this->valid_until);
    }
}
