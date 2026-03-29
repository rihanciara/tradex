<?php

use Illuminate\Support\Facades\Route;
use Modules\JerryUpdates\Http\Controllers\Api\ApiPosController;

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

// TODO: Replace 'auth:api' with Sanctum or a custom API Token guard for Next.js.
// For now, we wrap it in an API group. You can hit this locally via Postman with proper headers.
Route::prefix('jerryupdates/v1/pos')->middleware(['api'])->group(function () {
    
    // Hyper-optimized Product Catalog
    Route::get('catalog', [ApiPosController::class, 'getCatalog']);

    // Customer Debounced Search
    Route::get('customers', [ApiPosController::class, 'getCustomers']);

    // Raw POS Checkout Insertion
    Route::post('checkout', [ApiPosController::class, 'checkout']);

});
