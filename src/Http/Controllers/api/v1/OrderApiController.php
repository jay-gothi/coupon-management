<?php

namespace Woohoo\GoapptivCoupon\Http\Controllers\api\v1;

use Woohoo\GoapptivCoupon\Http\Controllers\api\BaseApiController;
use Woohoo\GoapptivCoupon\Http\Controllers\RestResponse;
use Woohoo\GoapptivCoupon\Http\Requests\CreateOrderRequest;
use Woohoo\GoapptivCoupon\Jobs\GenerateWoohooCoupon;
use Woohoo\GoapptivCoupon\Models\Order;

class OrderApiController extends BaseApiController {

    /**
     * Get order details
     *
     * @param $id
     * @return mixed
     */
    public function get($id) {
        $order = Order::with(['items', 'address', 'cards'])->where('order_id', $id)->first();
        if (!isset($order))
            return RestResponse::noContent();
        return RestResponse::done('order', $order);
    }

    /**
     * Create new order
     *
     * @param CreateOrderRequest $request
     * @return mixed
     */
    public function create(CreateOrderRequest $request) {
        $totalOrders = Order::where('ref_no', "GA-{$request->get('id')}")->count();
        if ($totalOrders > 0)
            return RestResponse::badRequest(['id' => 'id is already taken']);

        $orderJob = new GenerateWoohooCoupon(
            $request->getFields()->toArray(),
            $request->get('sku')
        );
        $this->dispatchNow($orderJob);
        return RestResponse::done('order', $orderJob->getResponse());
    }
}
