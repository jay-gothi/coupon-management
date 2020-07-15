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

class FetchWoohooOrderCards implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int order id */
    private $id;

    /**
     * Constructor
     *
     * @param $id
     */
    public function __construct($id) {
        $this->id = $id;
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
            "/rest/v3/order/{$this->id}/cards"
        );
    }

    /**
     * Save card details to order
     *
     * @param $data
     */
    private function saveData($data) {
        $order = Order::where('order_id', $this->id)->first();
        if (isset($data['cards'])) {
            foreach ($data['cards'] as $card) {
                $cardModel = Card::firstOrNew([
                    "sku" => $card['sku'],
                    "product_name" => $card['productName'],
                    "activation_code" => $card['activationCode'],
                    "activation_url" => $card['activationUrl'],
                    "amount" => $card['amount'],
                    "validity" => Carbon::createFromFormat(DateTime::ATOM, $card['validity']),
                    'recipient_details' => json_encode($card['recipientDetails'])
                ]);
                $cardModel->fill([
                    "order_id" => $order->id,
                    "card_number" => Utils::encrypt($card['cardNumber']),
                    "card_pin" => Utils::encrypt($card['cardPin'])
                ]);
                $cardModel->save();
                $cardModel->refresh();
                dispatch(new SendCouponEmail($cardModel->toArray()));
            }
            $order->status = 'COMPLETE';
            $order->save();
        }
    }
}
