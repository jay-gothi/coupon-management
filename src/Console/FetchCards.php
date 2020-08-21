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
        $orders = Order::where('status', "PROCESSING")->paginate(10);
        $orders->map(function ($order) {
            dispatch(new FetchWoohooOrderStatus($order->order_id));
        });
        Log::info("INITIATED CARDS FETCH.");
    }
}
