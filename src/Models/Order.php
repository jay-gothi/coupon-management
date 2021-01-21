<?php

namespace Woohoo\GoapptivCoupon\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Woohoo\GoapptivCoupon\Models\Account;

class Order extends TraceableBaseModel {

    /**
     * Table name
     */
    public static $TABLE = "woohoo_orders";

    protected $guarded = [];

    /**
     * Address
     *
     * @return BelongsTo
     */
    public function address() {
        return $this->belongsTo(Address::class);
    }

    /**
     * Order items
     *
     * @return HasMany
     */
    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Cards relation
     *
     * @return HasMany
     */
    public function cards() {
        return $this->hasMany(Card::class);
    }

    /**
     * Account relation
     *
     * @return BelongsTo
     */
    public function account() {
        return $this->belongsTo(Account::class);
    }
}
