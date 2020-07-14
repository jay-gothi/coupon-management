<?php

namespace Woohoo\GoapptivCoupon\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {

    /**
     * Table name
     */
    public static $TABLE;

    /**
     * Remove timestamp
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * ChannelPartnerOrganization constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->setTable(static::$TABLE);
    }
}
