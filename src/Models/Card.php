<?php

namespace Woohoo\GoapptivCoupon\Models;

class Card extends TraceableBaseModel {

    /**
     * Table name
     */
    public static $TABLE = "woohoo_cards";

    protected $guarded = [];

    /**
     * Get Recipient details
     *
     * @param string $value
     * @return string
     */
    public function getRecipientDetailsAttribute($value) {
        return json_decode($value, true);
    }
}
