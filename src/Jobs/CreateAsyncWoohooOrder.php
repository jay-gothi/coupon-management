<?php

namespace Woohoo\GoapptivCoupon\Jobs;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Configuration;
use Woohoo\GoapptivCoupon\Models\Order;
use Woohoo\GoapptivCoupon\Utils;

class CreateAsyncWoohooOrder implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int order id */
    private $orderId;

    /** @var Order order model */
    private $order;

    /**
     * Constructor
     *
     * @param $orderId
     */
    public function __construct($orderId) {
        $this->orderId = $orderId;
    }

    /**
     * Request for woohoo coupon
     */
    public function handle() {
        Log::info("REQUESTING COUPON FROM WOOHOO SERVER:");

        Log::info("Fetching order...");
        $this->order = Order::with(['items', 'address'])->find($this->orderId);

        Log::info("Creating order...");
        try {
            $response = $this->getClient()->request(
                'POST',
                $this->getUrl(),
                ['body' => json_encode($this->prepareData())]
            );
            if ($response->getStatusCode() == 200
                || $response->getStatusCode() == 201
                || $response->getStatusCode() == 202) {
                Log::info('Saving order details...');
                Log::info($response->getBody());
                $this->saveData(json_decode($response->getBody(), true));
                Log::info("COUPON REQUEST RAISED.");
            }
        } catch (RequestException $e) {
            $this->fail($e);
            Log::info("COUPON GENERATION FAILED.");
            Log::info($e->getResponse()->getBody());
            Log::info($e->getMessage());
        } catch (GuzzleException $e) {
            $this->fail($e);
            Log::error($e->getMessage());
            Log::info("COUPON GENERATION FAILED.");
        }
    }

    /**
     * Get woohoo server client
     *
     * @return Client
     */
    private function getClient() {
        return new Client([
            'base_uri' => env("WOOHOO_REWARDS_ENDPOINT"),
            'timeout' => 10.0,
            'headers' => $this->getHeaders()
        ]);
    }

    /**
     * Get headers
     *
     * @return array
     */
    private function getHeaders() {
        $configuration = Configuration::find(1);
        return [
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'dateAtClient' => Carbon::now()->toISOString(),
            'signature' => $this->generateSignature(),
            'Authorization' => 'Bearer ' . $configuration->token,
        ];
    }

    /**
     * Generate signature
     */
    private function generateSignature() {
        $data = $this->prepareData();
        Utils::sortParams($data);
        return Utils::encryptSignature(sprintf('%s&%s&%s',
            'POST',
            rawurlencode($this->getUrl()),
            rawurlencode(
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            )
        ));
    }

    /**
     * Get url
     *
     * @return string
     */
    private function getUrl() {
        return sprintf(
            "%s%s",
            env("WOOHOO_REWARDS_ENDPOINT"),
            "/rest/v3/orders"
        );
    }

    /**
     * Prepare Data
     *
     * @return array

     */
    private function prepareData() {
        return [
            "address" => [
                "firstname" => $this->order->address->first_name,
                "lastname" => "GoApptiv",
                "email" => $this->order->address->email,
                "telephone" => $this->order->address->telephone,
                "line1" => $this->order->address->line1,
                "line2" => $this->order->address->line2,
                "city" => $this->order->address->city,
                "region" => $this->order->address->region,
                "country" => "IN",
                "postcode" => $this->order->address->pin_code,
                "billToThis" => true
            ],
            "billing" => [
                "firstname" => $this->order->address->first_name,
                "lastname" => "GoApptiv",
                "email" => $this->order->address->email,
                "telephone" => $this->order->address->telephone,
                "line1" => $this->order->org_code,
                "line2" => $this->order->address->line2,
                "city" => $this->order->address->city,
                "region" => $this->order->address->region,
                "country" => "IN",
                "postcode" => $this->order->address->pin_code,
                "billToThis" => true
            ],
            "payments" => [[
                "code" => "svc",
                "amount" => $this->order->items[0]->price
            ]],
            "refno" => $this->order->ref_no,
            "products" => [[
                "sku" => $this->order->items[0]->sku,
                "price" => $this->order->items[0]->price,
                "qty" => 1,
                "currency" => 356,
            ]],
            "syncOnly" => false,
            "deliveryMode" => "API"
        ];
    }

    /**
     * Save order data
     *
     * @param $data
     */
    private function saveData($data) {
        if (isset($data['payments']) && count($data['payments']) > 0) {
            $this->order->current_balance = $data['payments'][0]['balance'];
        }
        $this->order->order_id = $data['orderId'];
        $this->order->status = 'PROCESSING';
        $this->order->save();
    }
}
