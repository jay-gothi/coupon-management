<?php

namespace Woohoo\GoapptivCoupon\Models;

use Illuminate\Support\Facades\Input;

class TraceableBaseModel extends BaseModel {

    /**
     * Remove timestamp
     *
     * @var bool
     */
    public $timestamps = true;
}
