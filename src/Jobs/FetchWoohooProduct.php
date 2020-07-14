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

class FetchWoohooProduct implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Invoice id to approve
     */
    private $sku;

    /**
     * Configuration model
     */
    private $configuration;


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
        $this->configuration = Configuration::find(1);

        Log::info("FETCHING WOOHOO PRODUCT:");

        Log::info("Fetching product...");
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
        Log::info('FETCHED WOOHOO PRODUCTS.');
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
        return [
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'dateAtClient' => Carbon::now()->toISOString(),
            'signature' => $this->generateSignature(),
            'Authorization' => 'Bearer ' . $this->configuration->token,
        ];
    }

    /**
     * Generate signature
     */
    private function generateSignature() {
        $baseString = 'GET' . '&' . rawurlencode($this->getUrl());
        return hash_hmac('sha512', $baseString, env('WOOHOO_CLIENT_SECRET'));
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
            'images_mobile' => $product['images']['mobile']
        ]);
        $productModel->save();
    }
}
