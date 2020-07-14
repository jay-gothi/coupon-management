<?php

namespace Woohoo\GoapptivCoupon;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actions\GoapptivCoupon\Skeleton\SkeletonClass
 */
class GoapptivCouponFacade extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'goapptiv-coupon';
    }
}
