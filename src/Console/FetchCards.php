<?php

namespace Woohoo\GoapptivCoupon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Jobs\FetchWoohooOrderStatus;
use Woohoo\GoapptivCoupon\Models\Order;

// TODO:: cron: every 5 min
class FetchCards extends Command {

    /** @var string generate woohoo coupon for orders. */
    protected $signature = 'fetch_cards';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Initialize generation of cards
     */
    public function handle() {
        Log::info("INITIALIZING CARD FETCH:");

        Log::info("Fetch processing orders...");
        $orders = $this->getFirst10ProcessingOrders();

        $orders->map(function ($order) {
            dispatch(new FetchWoohooOrderStatus($order->ref_no, $order->order_id));
        });

        Log::info("INITIATED CARDS FETCH.");
    }

    /**
     * Get first 10 under process order
     */
    private function getFirst10ProcessingOrders() {
        return Order::where('status', "PROCESSING")->paginate(10);
    }
}
