<?php

namespace Woohoo\GoapptivCoupon\Console;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Category;
use Woohoo\GoapptivCoupon\Models\Account;
use Woohoo\GoapptivCoupon\Utils;

// TODO:: cron: every month
class FetchWoohooCategories extends Command {

    /** @var string request woohoo product categories command */
    protected $signature = 'fetch_woohoo_categories';

    /** Account account model */
    protected $account;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Fetch and save categories
     */
    public function handle() {
        Log::info("REQUESTING WOOHOO CATEGORIES:");

        Log::info("Fetching first account...");
        $this->account = Account::where('status', 'active')->first();

        Log::info("Requesting categories...");
        try {
            $response = $this->getClient()->request('GET', $this->getUrl(), []);
            if ($response->getStatusCode() == 200) {
                Log::info('Saving categories...');
                $this->saveCategories(json_decode($response->getBody(), true));
                Log::info('SAVED WOOHOO CATEGORIES.');
            }
        } catch (RequestException $e) {
            Log::error($e->getMessage());
            Log::info("TOKEN GENERATION FAILED.");
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            Log::info("TOKEN GENERATION FAILED.");
        }
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
            "/rest/v3/catalog/categories"
        );
    }

    /**
     * Get woohoo server client
     *
     * @return Client
     */
    private function getClient() {
        return new Client([
            'base_uri' => $this->account->endpoint,
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
            'Authorization' => 'Bearer ' . $this->account->token,
        ];
    }

    /**
     * Generate signature
     */
    private function generateSignature() {
        return Utils::encryptSignature(sprintf(
            "%s&%s",
            'GET',
            rawurlencode($this->getUrl())
        ), $this->account->client_secret);
    }

    /**
     * Save category to database
     *
     * @param $data
     */
    private function saveCategories($data) {
        $category = Category::firstOrNew([
            'id' => $data['id'],
            'name' => $data['name'],
            'url' => $data['url'],
            'parent_id' => isset($data['parent_id']) ? $data['parent_id'] : null,
            'description' => $data['description'],
            'subcategoriesCount' => $data['subcategoriesCount']
        ]);
        $category->save();
        if ($data['subcategoriesCount'] > 0) {
            foreach ($data['subcategories'] as $sub) {
                $sub['parent_id'] = $category->id;
                $this->saveCategories($sub);
            }
        }
    }
}
