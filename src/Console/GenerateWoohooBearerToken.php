<?php

namespace Woohoo\GoapptivCoupon\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Configuration;

class GenerateWoohooBearerToken extends Command {

    /** @var string generate woohoo bearer token command. */
    protected $signature = 'generate_bearer_woohoo_token';

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Request bearer token from woohoo server
     * using the credentials
     */
    public function handle() {
        Log::info("REQUESTING BEARER TOKEN:");

        Log::info("Requesting token...");
        $client = $this->getClient();

        try {
            $response = $client->request(
                'POST',
                env("WOOHOO_REWARDS_ENDPOINT") . '/oauth2/token',
                ['body' => json_encode($this->prepareData())]
            );
            if ($response->getStatusCode() == 200) {
                Log::info('Saving token...');
                $this->saveToken(json_decode($response->getBody(), true));
                Log::info('TOKEN GENERATION COMPLETED.');
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
            'Accept' => '*/*'
        ];
    }

    /**
     * Prepare Data
     *
     * @return array
     */
    private function prepareData() {
        $configuration = Configuration::with([])->firstOrNew(["id" => 1]);
        return [
            "clientId" => env('WOOHOO_CLIENT_ID'),
            "clientSecret" => env('WOOHOO_CLIENT_SECRET'),
            "authorizationCode" => $configuration->authorization_code
        ];
    }

    /**
     * Save bearer token in configuration
     *
     * @param $data
     */
    private function saveToken($data) {
        $configuration = Configuration::with([])->firstOrNew(["id" => 1]);
        $configuration->fill(['token' => $data['token']]);
        $configuration->save();
    }
}
