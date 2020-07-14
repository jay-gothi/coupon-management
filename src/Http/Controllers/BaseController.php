<?php

namespace Woohoo\GoapptivCoupon\Http\Controllers;

use Illuminate\Support\Facades\Request;
use Woohoo\GoapptivCoupon\Utils;

/**
 * Class BaseController
 *
 * This is the Base call for all the Controller classes.
 * Every Controller class should extend this class
 *
 * @package App\Http\Controllers
 */
abstract class BaseController extends Controller {

    /** @var int page length */
    protected $pageLength;

    /**
     * Base Api Controller constructor
     */
    public function __construct() {
        $this->setPageLength();
    }

    /**
     * get pagination length from get parameter
     */
    public function setPageLength() {
        $pageLength = Request::get('length');
        if (!isset($pageLength))
            $pageLength = Utils::$PAGE_LENGTH;

        $this->pageLength = $pageLength;
    }
}
