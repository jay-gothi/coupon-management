<?php

namespace Woohoo\GoapptivCoupon\Console;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Jobs\FetchWoohooProduct;
use Woohoo\GoapptivCoupon\Models\Category;
use Woohoo\GoapptivCoupon\Models\Configuration;
use Woohoo\GoapptivCoupon\Models\Product;
use Woohoo\GoapptivCoupon\Utils;

// TODO:: cron: every month
class FetchWoohooProducts extends Command {

    /** @var string request woohoo products command */
    protected $signature = 'fetch_woohoo_products';

    /**
     * Category
     */
    private $category;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Fetch and save product list from woohoo server
     * for the assigned category
     */
    public function handle() {
        Log::info("FETCHING WOOHOO PRODUCTS:");
        Log::info("Fetching products...");
        try {
            $response = $this->getClient()->request('GET', $this->getUrl(), []);
            if ($response->getStatusCode() == 200) {
                Log::info('Saving data...');
                $this->saveData(json_decode($response->getBody(), true));
                Log::info('PRODUCTS FETCHED SUCCESSFULLY.');
            }
        } catch (RequestException $e) {
            Log::error($e->getMessage());
            Log::info("PRODUCTS FETCH FAILED.");
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            Log::info("PRODUCTS FETCH FAILED.");
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
        return Utils::encryptSignature(sprintf(
            '%s&%s',
            'GET',
            rawurlencode($this->getUrl())
        ));
    }

    /**
     * Get url
     *
     * @return string
     */
    private function getUrl() {
        $category = Category::whereNull('parent_id')->first();
        return sprintf(
            "%s%s",
            env("WOOHOO_REWARDS_ENDPOINT"),
            "/rest/v3/catalog/categories/{$category->id}/products"
        );
    }

    /**
     * Save data
     *
     * @param $data
     */
    private function saveData($data) {
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->createProduct($product);
            }
        }
    }

    /**
     * Save product to database
     *
     * @param $data
     */
    private function createProduct($data) {
        $product = Product::firstOrNew([
            'sku' => $data['sku'],
            'name' => $data['name'],
            'currency' => $data['currency']['code'],
            'url' => $data['url'],
            'minPrice' => $data['minPrice'],
            'maxPrice' => $data['maxPrice'],
            'images_thumbnail' => $data['images']['thumbnail'],
            'images_small' => $data['images']['small'],
            'images_mobile' => $data['images']['mobile'],
        ]);
        $product->save();
        dispatch(new FetchWoohooProduct($product->sku));
    }
}
