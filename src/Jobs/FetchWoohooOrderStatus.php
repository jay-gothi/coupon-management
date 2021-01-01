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

    /** @var Order order model */
    private $order;

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

        Log::info("Fetching order details...");
        $this->order = Order::with(['account'])->where('order_id', $this->id)->first();

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
            $this->markOrderFailed();
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            Log::info("CARDS FETCH FAILED.");
            $this->markOrderFailed();
        }
    }

    /**
     * Get woohoo server client
     *
     * @return Client
     */
    private function getClient() {
        return new Client([
            'base_uri' => $this->order->account->endpoint,
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
        return [
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'dateAtClient' => Carbon::now()->format('Y-m-d\TH:i:s.u\Z'),
            'signature' => $this->generateSignature(),
            'Authorization' => 'Bearer ' . $this->order->account->token,
        ];
    }

    /**
     * Generate signature
     */
    private function generateSignature() {
        return Utils::encryptSignature('GET' . '&' . rawurlencode($this->getUrl()), $this->order->account->client_secret);
    }

    /**
     * Get url
     *
     * @return string
     */
    private function getUrl() {
        return sprintf(
            "%s%s",
            $this->order->account->endpoint,
            "/rest/v3/order/{$this->ref_no}/status"
        );
    }

    /**
     * Save card details to order
     *
     * @param $data
     */
    private function saveData($data) {
        $this->order->attempts = $this->order->attempts + 1;

        if ($data['status'] == Utils::$COMPLETE) {
            dispatch(new FetchWoohooOrderCards($this->id));
        } else if (($data['status'] == Utils::$CANCELLED) || ($this->order->attempts >= 2)) {
            $this->order->status = Utils::$CANCELLED;
        }
        $this->order->save();
    }

    /**
     * Mark order as failed
     */
    private function markOrderFailed() {
        $this->order->attempts = $order->attempts + 1;
        $this->order->status = Utils::$CANCELLED;
        $this->order->save();
    }
}
