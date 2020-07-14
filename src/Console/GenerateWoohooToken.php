<?php

namespace Woohoo\GoapptivCoupon\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Configuration;
use Woohoo\GoapptivCoupon\Utils;

// TODO:: cron: every month
class GenerateWoohooToken extends Command {

    /** @var string generate woohoo token command.*/
    protected $signature = 'generate_woohoo_token';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Request woohoo server for the token
     * using credentials
     */
    public function handle() {
        Log::info("REQUESTING AUTH TOKEN FROM WOOHOO SERVER:");

        Log::info("Requesting token...");
        $client = $this->getClient();

        try {
            $response = $client->request(
                'POST',
                env("WOOHOO_REWARDS_ENDPOINT") . '/oauth2/verify',
                ['body' => json_encode($this->prepareBody())]
            );
            if ($response->getStatusCode() == 200) {
                Log::info('Saving token...');
                $this->saveToken(json_decode($response->getBody(), true));
                Log::info('WOOHOO TOKEN SAVED.');
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
     * Get request client
     *
     * @return Client
     */
    private function getClient() {
        return new Client([
            'base_uri' => env("WOOHOO_REWARDS_ENDPOINT"),
            'timeout' => Utils::$REQUEST_TIMEOUT,
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
     * Prepare request data
     *
     * @return array
     */
    private function prepareBody() {
        return [
            "clientId" => env('WOOHOO_CLIENT_ID'),
            "username" => env('CREDENCE_LOGIN_USERNAME'),
            "password" => env('CREDENCE_LOGIN_PASSWORD'),
        ];
    }

    /**
     * Save woohoo token in configuration
     *
     * @param $data
     */
    private function saveToken($data) {
        $configuration = Configuration::with([])->firstOrNew(["id" => 1]);
        $configuration->fill([
            'authorization_code' => $data['authorizationCode']
        ]);
        $configuration->save();
    }
}
