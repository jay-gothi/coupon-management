<?php

namespace Woohoo\GoapptivCoupon\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Address;
use Woohoo\GoapptivCoupon\Models\Order;
use Woohoo\GoapptivCoupon\Models\OrderItem;
use Woohoo\GoapptivCoupon\Utils;

class GenerateWoohooCoupon implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array Coupon request */
    private $request;

    /** @var string Sku */
    private $sku;

    /** @var Order order model */
    private $order;

    /**
     * Constructor
     *
     * @param $request
     * @param $sku
     */
    public function __construct($request, $sku) {
        $this->request = $request;
        $this->sku = $sku;
        $this->connection = 'sync';
    }

    /**
     * Request for woohoo coupon
     */
    public function handle() {
        Log::info("RAISING COUPON REQUEST:");

        Log::info("Creating order...");
        $this->order = $this->createOrder();
        dispatch(new CreateAsyncWoohooOrder($this->order->id));

        Log::info("COUPON REQUEST RAISED.");
    }

    /**
     * Get response as order model
     *
     * @return Order
     */
    public function getResponse() {
        return $this->order;
    }

    /**
     * Save order details to db
     */
    private function createOrder() {
        $address = Address::create([
            "first_name" => $this->request['full_name'],
            "last_name" => "GoApptiv",
            "email" => $this->request['email'],
            "telephone" => $this->request['mobile'],
            "line1" => $this->request['address']['line'],
            "line2" => $this->request['address']['landmark'],
            "city" => $this->request['address']['city'],
            "region" => $this->request['address']['state'],
            "country" => "IN",
            "postcode" => $this->request['address']['pin_code'],
        ]);
        $order = Order::create([
            'ref_no' => Utils::convertToRefNo($this->request['id']),
            'address_id' => $address->id,
            'billing_id' => $address->id,
            'coupon_code' => '',
            'status' => 'PENDING',
            'delivery_mode' => 'API',
            'org_code' => $this->request['org_code'],
            'org_name' => $this->request['org_name']
        ]);
        OrderItem::create([
            "order_id" => $order->id,
            "sku" => $this->sku,
            "price" => $this->request['amount'],
            "qty" => 1,
            "currency" => 356,
            "giftMessage" => ""
        ]);
        $order->load(['items', 'address']);
        return $order;
    }
}
