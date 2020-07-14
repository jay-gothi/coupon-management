<?php

namespace Woohoo\GoapptivCoupon\Http\Controllers\api;

use Woohoo\GoapptivCoupon\Http\Controllers\BaseController;

/**
 * Class BaseApiController
 *
 * Base Class for all the Api Controllers
 * provides basic functions for responses
 * provides bad_request, done, no_content, not_found functions
 *
 * @package App\Http\Controllers\api\v1
 */
abstract class BaseApiController extends BaseController {

    /**
     * Api Controller constructor
     */
    public function __construct() {
        parent::__construct();
    }
}
