<?php

namespace Woohoo\GoapptivCoupon\Http\Requests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Order;

class CreateOrderRequest extends Request {

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
        $orderTable = Order::$TABLE;
        return [
            'sku' => array('required'),
            'full_name' => array('required'),
            'email' => array('required'),
            'mobile' => array('required'),
            'amount' => array('required'),
            'id' => array('required', "unique:{$orderTable},id"),
            'address' => array('required', 'array'),
            'address.line' => array('required'),
            'address.landmark' => array('required'),
            'address.city' => array('required'),
            'address.state' => array('required'),
            'address.pin_code' => array('required'),
        ];
    }

    /**
     * Get formatted request fields
     *
     * @return Collection
     */
    public function getFields() {
        $address = collect($this->get('address'));
        return collect([
            'full_name' => $this->get('full_name'),
            'email' => $this->get('email'),
            'mobile' => $this->get('mobile'),
            'amount' => $this->get('amount'),
            'id' => $this->get('id'),
            'address' => [
                'line' => $address->get('line'),
                'landmark' => $address->get('landmark'),
                'city' => $address->get('city'),
                'state' => $address->get('state'),
                'pin_code' => $address->get('pin_code')
            ]
        ])->filter(function ($value, $key) {
            return $value != null;
        });
    }
}
