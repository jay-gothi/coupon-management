<?php

namespace Woohoo\GoapptivCoupon\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Postmark\PostmarkClient;
use Woohoo\GoapptivCoupon\Models\Product;
use Woohoo\GoapptivCoupon\Utils;

class SendCouponEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** card data */
    private $card;

    /**
     * Send email
     *
     * @param $card
     */
    public function __construct($card)
    {
        $this->card = $card;
        $this->queue = 'wohoo_coupon_queue';
    }

    /**
     * Send email
     */
    public function handle()
    {
        $client = new PostmarkClient(env('POSTMARK_SECRET'));
        $product = Product::where('sku', $this->card['sku'])->first();

        $template_id = "coupon-code-email";
        if (isset($this->card['activation_url']) && !is_null($this->card['activation_url'])) {
            $template_id = "coupon-code-email-activation";
        }

        $client->sendEmailWithTemplate(
            "info@goapptiv.com",
            $this->card['recipient_details']['email'],
            $template_id,
            [
                "firm_name" => $this->card['recipient_details']['name'],
                "vendor" => $this->card['product_name'],
                "amount" => $this->card['amount'],
                "term_conditions" => $product->terms,
                "expiry_date" => $this->card['validity'],
                "card_no" => Utils::decrypt($this->card['card_number']),
                "card_pin" => Utils::decrypt($this->card['card_pin']),
                'activation_url' => $this->card['activation_url']
            ]
        );

        return null;
    }
}
