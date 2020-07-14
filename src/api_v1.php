<?php

use Illuminate\Support\Facades\Route;
use Woohoo\GoapptivCoupon\Http\Controllers\api\v1\AuthApiController;
use Woohoo\GoapptivCoupon\Http\Controllers\api\v1\OrderApiController;

Route::middleware('ga_token')->prefix("auth")->group(function () {
    Route::post('', [AuthApiController::class, 'login']);
});

Route::middleware(['auth_token', 'ga_token'])->prefix("orders")->group(function () {
    Route::post('', [OrderApiController::class, 'create']);
    Route::get('{id}', [OrderApiController::class, 'get']);
});
