<?php

namespace Woohoo\GoapptivCoupon\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Postmark\PostmarkClient;
use Woohoo\GoapptivCoupon\Utils;

class SendCouponEmail implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** card data */
    private $card;

    /**
     * Send email
     *
     * @param $card
     */
    public function __construct($card) {
        $this->card = $card;
    }

    /**
     * Send email
     */
    public function handle() {
        $client = new PostmarkClient(env('POSTMARK_SECRET'));

        $client->sendEmailWithTemplate(
            "info@goapptiv.com",
            $this->card['recipient_details']['email'],
            "coupon-code-email",
            [
                "firm_name" => $this->card['recipient_details']['name'],
                "vendor" => $this->card['product_name'],
                "amount" => $this->card['amount'],
                "card_no" => Utils::decrypt($this->card['card_number']),
                "card_pin" => Utils::decrypt($this->card['card_pin'])
            ]
        );

        return null;
    }
}
