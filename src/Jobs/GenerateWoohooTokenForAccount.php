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

class GenerateWoohooTokenForAccount implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int account id */
    private $accountId;

    /** @var Account account model */
    private $account;

    /**
     * Constructor
     *
     * @param $orderId
     */
    public function __construct($accountId)
    {
        $this->accountId = $accountId;
        $this->queue = 'wohoo_coupon_queue';
    }

    /**
     * Request woohoo server for the token
     *
     * using credentials
     */
    public function handle()
    {
        Log::info("REQUESTING AUTH TOKEN FROM WOOHOO SERVER FOR ACCOUNT:" . $this->accountId);

        Log::info("Fetching account...");
        $this->account = Account::with([])->find($this->accountId);

        Log::info("Requesting token...");
        $this->requestToken();
    }

    /**
     * Request token for account from woohoo server
     */
    private function requestToken()
    {
        $client = $this->getClient();
        try {
            $response = $client->request(
                'POST',
                $this->account->endpoint . '/oauth2/verify',
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
    private function getClient()
    {
        return new Client([
            'base_uri' => $this->account->endpoint,
            'timeout' => Utils::$REQUEST_TIMEOUT,
            'headers' => $this->getHeaders()
        ]);
    }

    /**
     * Get headers
     *
     * @return array
     */
    private function getHeaders()
    {
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
    private function prepareBody()
    {
        return [
            "clientId" => $this->account->client_id,
            "username" => $this->account->login_username,
            "password" => $this->account->login_password,
        ];
    }

    /**
     * Save woohoo token in configuration
     *
     * @param $data
     */
    private function saveToken($data)
    {
        $this->account->fill([
            'authorization_code' => $data['authorizationCode']
        ]);
        $this->account->save();
    }
}
