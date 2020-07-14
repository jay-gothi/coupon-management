<?php

namespace Woohoo\GoapptivCoupon\Http\Requests;

class LoginRequest extends Request {

    /**
     * Always return true
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Validation login request
     *
     * @return array
     */
    public function rules() {
        return [
            'username' => array('required'),
            'password' => array('required')
        ];
    }
}
