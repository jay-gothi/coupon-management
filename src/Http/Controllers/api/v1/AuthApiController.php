<?php

namespace Woohoo\GoapptivCoupon\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use Woohoo\GoapptivCoupon\Http\Controllers\api\BaseApiController;
use Woohoo\GoapptivCoupon\Http\Controllers\RestResponse;
use Woohoo\GoapptivCoupon\Http\Requests\LoginRequest;
use Woohoo\GoapptivCoupon\Models\Configuration;

class AuthApiController extends BaseApiController {

    /**
     * Get auth details
     *
     * @param LoginRequest $request
     * @return mixed
     */
    public function login(LoginRequest $request) {
        if (!$this->validateRequest(
            $request->get('username'),
            $request->get('password')
        )) {
            return RestResponse::badRequest(['error' => 'Invalid login details']);
        }

        $configuration = Configuration::find(1);
        if (!isset($configuration))
            return RestResponse::badRequest(['errors' => 'Some problem on the server side. Please contact admin team.']);

        return RestResponse::done('auth_token', $configuration->authorization_code);
    }

    /**
     * Validate request
     *
     * @param Request $username
     * @param array $password
     * @return array|bool
     */
    private function validateRequest($username, $password) {
        if ($username === env('GA_COUPON_USERNAME')
            && $password === env("GA_COUPON_PASSWORD"))
            return true;
        return false;
    }
}
