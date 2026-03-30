<?php

use Illuminate\Support\Facades\Route;
use Modules\JerryUpdates\Http\Controllers\Api\ApiPosController;
use Modules\JerryUpdates\Http\Controllers\Api\ApiAuthController;

/*
|--------------------------------------------------------------------------
| VERCEL NEXT.JS HEADLESS API ROUTES
|--------------------------------------------------------------------------
|
| These endpoints bypass traditional Blade/jQuery setups and provide
| high-performance, raw JSON payloads optimized for React Query and
| the Next.js decoupled frontend architecture.
|
*/

Route::prefix('v1/auth')->middleware(['api'])->group(function () {
    Route::post('login', [ApiAuthController::class, 'login']);
});

// We wrap POS endpoints in an API group. You can hit this locally via Postman with proper headers.
Route::prefix('v1/pos')->middleware(['api', 'auth:api'])->group(function () {
    
    Route::get('profile', [ApiAuthController::class, 'profile']);
    Route::post('logout', [ApiAuthController::class, 'logout']);

    // Initial Load / Settings Payload
    Route::get('init', [ApiPosController::class, 'init']);

    // Taxonomies
    Route::get('taxonomies', [ApiPosController::class, 'getTaxonomies']);

    // Hyper-optimized Product Catalog
    Route::get('catalog', [ApiPosController::class, 'getCatalog']);

    // Customer Debounced Search
    Route::get('customers', [ApiPosController::class, 'getCustomers']);

    // Raw POS Checkout Insertion
    Route::post('checkout', [ApiPosController::class, 'checkout']);

});
