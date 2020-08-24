<?php

namespace Woohoo\GoapptivCoupon\Jobs;

use Carbon\Carbon;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Card;
use Woohoo\GoapptivCoupon\Models\Configuration;
use Woohoo\GoapptivCoupon\Models\Order;
use Woohoo\GoapptivCoupon\Utils;
use Woohoo\GoapptivCoupon\Jobs\FetchWoohooOrderCards;

class FetchWoohooOrderStatus implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int order id */
    private $id;

    /** @var string ref no */
    private $ref_no;

    /**
     * Constructor
     *
     * @param $id
     */
    public function __construct($ref_no, $id) {
        $this->id = $id;
        $this->ref_no = $ref_no;
    }

    /**
     * Get cards for order
     */
    public function handle() {
        Log::info("FETCHING WOOHOO CARDS FOR ORDER:");

        Log::info("Fetching cards for order...");
        try {
            $response = $this->getClient()->request('GET', $this->getUrl(), []);
            if ($response->getStatusCode() == 200) {
                Log::info('Saving product...');
                Log::info($response->getBody());
                $this->saveData(json_decode($response->getBody(), true));
                Log::info('FETCHED WOOHOO CARDS FOR ORDER.');
            }
        } catch (RequestException $e) {
            Log::error($e->getMessage());
            Log::info("CARDS FETCH FAILED.");
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            Log::info("CARDS FETCH FAILED.");
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
        return Utils::encryptSignature('GET' . '&' . rawurlencode($this->getUrl()));
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
            "/rest/v3/order/{$this->ref_no}/status"
        );
    }

    /**
     * Save card details to order
     *
     * @param $data
     */
    private function saveData($data) {
        $order = Order::where('order_id', $this->id)->first();
        if ($data['status'] == Utils::$COMPLETE) {
            dispatch(new FetchWoohooOrderCards($this->id));
        } else if ($data['status'] == Utils::$CANCELLED) {
            $order->status = Utils::$CANCELLED;
        }
    }
}
