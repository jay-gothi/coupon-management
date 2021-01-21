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

    /** $var Order order */
    private $order;

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

        Log::info("Fetching order for : ". $this->id);
        $this->order = Order::with(['account'])->where('order_id', $this->id)->first();

        Log::info("Fetching cards for order...");
        $this->fetchCards();
    }

    /**
     * Fetch cards
     */
    private function fetchCards() {
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
        $date = Carbon::now();
        $date = $date->setTimezone('UTC');
        return [
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'dateAtClient' => $date->format('Y-m-d\TH:i:s.u\Z'),
            'signature' => $this->generateSignature(),
            'Authorization' => 'Bearer ' . $this->order->account->token,
        ];
    }

    /**
     * Generate signature
     */
    private function generateSignature() {
        return Utils::encryptSignature('GET' . '&' . rawurlencode($this->getUrl()),
            $this->order->account->client_secret);
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
            "/rest/v3/order/{$this->id}/cards"
        );
    }

    /**
     * Save card details to order
     *
     * @param $data
     */
    private function saveData($data) {
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
                    "order_id" => $this->order->id,
                    "card_number" => Utils::encrypt($card['cardNumber']),
                    "card_pin" => Utils::encrypt($card['cardPin'])
                ]);
                $cardModel->save();
                $cardModel->refresh();
                dispatch(new SendCouponEmail($cardModel->toArray()));
            }
            $this->order->status = 'COMPLETE';
            $this->order->save();
        }
    }
}
