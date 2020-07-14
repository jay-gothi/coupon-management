<?php

namespace Woohoo\GoapptivCoupon\Http\Middleware;

use Closure;
use Woohoo\GoapptivCoupon\Http\Controllers\RestResponse;

class GoApptivTokenMiddleware {
    public function handle($request, Closure $next) {
        if ($request->headers->get('ga-token') != env('GA_TOKEN'))
            return RestResponse::noPermission();
        return $next($request);
    }
}
