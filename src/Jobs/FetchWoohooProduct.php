<?php

namespace Woohoo\GoapptivCoupon\Jobs;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Configuration;
use Woohoo\GoapptivCoupon\Models\Product;
use Woohoo\GoapptivCoupon\Models\Account;

class FetchWoohooProduct implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Invoice id to approve
     */
    private $sku;

    /**
     * Configuration model
     */
    private $account;

    /**
     * Create a new job instance.
     *
     * @param $sku
     */
    public function __construct($sku) {
        $this->sku = $sku;
    }

    /**
     * Execute the console command.
     */
    public function handle() {
        Log::info("FETCHING WOOHOO PRODUCT:");

        Log::info("Fetching first active account...");
        $this->account = Account::where('status', 'active')->first();

        Log::info("Fetching product...");
        $this->fetchProducts();

        Log::info('FETCHED WOOHOO PRODUCTS.');
    }

    /**
     * Fetch products
     */
    private function fetchProducts() {
        $client = $this->getClient();
        try {
            $response = $client->request('GET', $this->getUrl(), []);
            if ($response->getStatusCode() == 200) {
                Log::info('Saving product...');
                $this->createProduct(json_decode($response->getBody(), true));
            }
        } catch (GuzzleException $e) {
            Log::info("Token generation Failed.");
            Log::error($e->getMessage());
        }
    }

    /**
     * Get credence server client
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
        $date = Carbon::now();
        $date = $date->setTimezone('UTC');
        return [
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'dateAtClient' => $date->format('Y-m-d\TH:i:s.u\Z'),
            'signature' => $this->generateSignature(),
            'Authorization' => 'Bearer ' . $this->account->token,
        ];
    }

    /**
     * Generate signature
     */
    private function generateSignature() {
        $baseString = 'GET' . '&' . rawurlencode($this->getUrl());
        return hash_hmac('sha512', $baseString, $this->account->client_secret);
    }

    /**
     * Get url
     *
     * @return string
     */
    private function getUrl() {
        return sprintf(
            "%s%s",
            $this->account->endpoint,
            "/rest/v3/catalog/products/{$this->sku}"
        );
    }

    /**
     * Save product to database
     *
     * @param $product
     */
    private function createProduct($product) {
        $productModel = Product::firstOrNew([
            'sku' => $product['sku'],
            'name' => $product['name']
        ]);
        $productModel->fill([
            'product_id' => $product['id'],
            'description' => $product['description'],
            'type' => $product['type'],
            'price_type' => $product['price']['type'],
            'minPrice' => $product['price']['min'],
            'maxPrice' => $product['price']['max'],
            'denominations' => json_encode($product['price']['denominations']),
            'images_thumbnail' => $product['images']['thumbnail'],
            'images_small' => $product['images']['small'],
            'images_mobile' => $product['images']['mobile'],
            'terms' => $product['tnc']['content']
        ]);
        $productModel->save();
    }
}
