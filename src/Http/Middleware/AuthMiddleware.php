<?php

namespace Woohoo\GoapptivCoupon\Http\Middleware;

use Closure;
use Woohoo\GoapptivCoupon\Http\Controllers\RestResponse;
use Woohoo\GoapptivCoupon\Models\Configuration;

class AuthMiddleware {
    public function handle($request, Closure $next) {
        $configuration = Configuration::find(1);
        if ($request->headers->get('auth-token') != $configuration->authorization_code)
            return RestResponse::noPermission();
        return $next($request);
    }
}
