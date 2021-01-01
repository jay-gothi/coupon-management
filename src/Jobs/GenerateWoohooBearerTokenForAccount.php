<?php

namespace Woohoo\GoapptivCoupon\Jobs;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Account;
use Woohoo\GoapptivCoupon\Utils;

class GenerateWoohooBearerTokenForAccount implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int account id */
    private $accountId;

    /** @var Account account model */
    private $account;

    /**
     * Constructor
     *
     * @param $orderId
     */
    public function __construct($accountId) {
        $this->accountId = $accountId;
    }

    /**
     * Request bearer token from woohoo server
     * using the credentials
     */
    public function handle() {
        Log::info("REQUESTING BEARER TOKEN FOR ACCOUNT: ". $this->accountId);

        Log::info("Fetching account...");
        $this->account = Account::with([])->find($this->accountId);
        
        Log::info("Requesting token...");
        $this->requestToken();
    }

    private function requestToken() {
        $client = $this->getClient();

        try {
            $response = $client->request(
                'POST',
                $this->account->endpoint . '/oauth2/token',
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
            'Accept' => '*/*'
        ];
    }

    /**
     * Prepare Data
     *
     * @return array
     */
    private function prepareData() {
        return [
            "clientId" => $this->account->client_id,
            "clientSecret" => $this->account->client_secret,
            "authorizationCode" => $this->account->authorization_code
        ];
    }

    /**
     * Save bearer token in configuration
     *
     * @param $data
     */
    private function saveToken($data) {
        $this->account->fill(['token' => $data['token']]);
        $this->account->save();
    }
}
