<?php

use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Merchant REST API. Bearer token (api_tokens) auth. Base: /api/v1
// Route names are prefixed with "api." so they never collide with the web
// resource route names (products.*, orders.*, customers.*).
Route::prefix('v1')->name('api.')->middleware('api.token')->group(function () {
    Route::get('me', fn (Request $r) => $r->user()->only(['id', 'name', 'email', 'role']));

    // Catalog + commerce read/write, mirroring the admin controllers.
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    Route::apiResource('collections', \App\Http\Controllers\Api\CollectionController::class);
    Route::apiResource('orders', \App\Http\Controllers\Api\OrderController::class)->only(['index', 'show', 'update']);
    Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);
    Route::apiResource('discounts', \App\Http\Controllers\Api\DiscountController::class);

    // Access & administration.
    Route::apiResource('users', UserController::class);
    Route::apiResource('api-tokens', ApiTokenController::class)->only(['index', 'store', 'destroy'])->parameters(['api-tokens' => 'apiToken']);
});
