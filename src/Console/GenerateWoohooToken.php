<?php

namespace Woohoo\GoapptivCoupon\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Woohoo\GoapptivCoupon\Models\Configuration;
use Woohoo\GoapptivCoupon\Utils;
use Woohoo\GoapptivCoupon\Jobs\GenerateWoohooTokenForAccount;
use Woohoo\GoapptivCoupon\Models\Account;

// TODO:: cron: every 7 days
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
        Log::info("REQUESTING AUTH TOKEN FOR ALL ACCOUNTS:");

        $accounts = $this->getListOfAccounts();
        foreach($accounts as $account) {
            dispatch(new GenerateWoohooTokenForAccount($account->id));
        }

        Log::info("TOKEN REQUEST RAISED FOR ALL THE ACCOUNTS:");
    }

    /**
     * Get list of accounts
     */
    private function getListOfAccounts() {
        return Account::with([])->get();
    }
}
