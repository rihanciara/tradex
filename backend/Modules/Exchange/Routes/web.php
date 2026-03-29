<?php
/*
|--------------------------------------------------------------------------
| Exchange Module Web Routes - Updated for Fixes
|--------------------------------------------------------------------------
|
| This file is located at: Modules/Exchange/Routes/web.php
| Updated to support all the bug fixes and new functionality
|
*/

/*
|--------------------------------------------------------------------------
| Module Installation Routes
|--------------------------------------------------------------------------
| These routes use minimal middleware to avoid accessing exchange tables
| before they are created during installation.
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('exchange')->name('exchange.')->group(function () {
        // Installation routes - minimal middleware to avoid table access issues
        Route::get('/install', [Modules\Exchange\Http\Controllers\InstallController::class, 'index'])->name('install');
        Route::get('/install/update', [Modules\Exchange\Http\Controllers\InstallController::class, 'update'])->name('install.update');
        Route::get('/install/uninstall', [Modules\Exchange\Http\Controllers\InstallController::class, 'uninstall'])->name('install.uninstall');
    });
});

/*
|--------------------------------------------------------------------------
| Main Exchange Routes
|--------------------------------------------------------------------------
| These routes are only loaded if the Exchange module is installed.
| This prevents middleware from accessing non-existent tables.
*/
Route::middleware([
    'web',              // Web middleware group
    'auth',             // Standard authentication
    'SetSessionData',   // Set session data for POS
    'language',         // Language localization
    'timezone',         // Timezone handling
    'AdminSidebarMenu'  // Admin sidebar menu setup
])->group(function () {

    // Only load these routes if the Exchange module is installed
    if (\App\System::getProperty('Exchange_version')) {

        Route::prefix('exchange')->name('exchange.')->group(function () {

            // Main Exchange Pages
            Route::get('/', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'index'])->name('index');
            Route::get('/create', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'create'])->name('create');

            // FIXED: Store route - ensure this matches your JavaScript AJAX calls
            Route::post('/store', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'store'])->name('store');
            Route::post('/', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'store'])->name('store_alias');

            // AJAX/API Routes (specific routes before parameterized ones)
            Route::get('/list', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'getExchanges'])->name('list');
            Route::post('/search-transaction', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'searchTransaction'])->name('search_transaction');

            // Product and Tax Data Routes
            Route::get('/products/suggestion', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'getProductSuggestion'])->name('product_suggestion');
            Route::get('/tax-rates', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'getTaxRates'])->name('tax_rates');

            // Debug Route (remove in production)
            Route::post('/debug', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'debugExchange'])->name('debug');

            // Individual Exchange Routes (parameterized routes last to avoid conflicts)
            Route::get('/{id}', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'show'])->name('show');

            // FIXED: Print routes - ensure these work with the new receipt templates
            Route::get('/{id}/print', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'printReceipt'])->name('print');
            Route::get('/{id}/print-receipt', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'printReceipt'])->name('print-receipt');

            // NEW: Print-only route for clean receipt printing (addresses Issue #5)
            Route::get('/{id}/print-only', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'printReceiptOnly'])->name('print-only');

            // FIXED: Management routes with proper HTTP methods
            Route::post('/{id}/cancel', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'cancel'])->name('cancel');
            Route::delete('/{id}', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'destroy'])->name('destroy');
        });
    }
});

/*
|--------------------------------------------------------------------------
| Additional API Routes for Enhanced Functionality
|--------------------------------------------------------------------------
| These routes support the enhanced features and bug fixes
*/
Route::middleware(['web', 'auth'])->group(function () {
    if (\App\System::getProperty('Exchange_version')) {
        Route::prefix('api/exchange')->name('api.exchange.')->group(function () {

            // ENHANCED: Validation and data fetching routes
            Route::post('/validate-items', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'validateExchangeItems'])->name('validate_items');
            Route::get('/transaction/{id}/items', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'getTransactionItems'])->name('transaction_items');

            // ENHANCED: Stock checking routes  
            Route::post('/check-stock', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'checkProductStock'])->name('check_stock');

            // ENHANCED: Receipt data routes
            Route::get('/{id}/receipt-data', [Modules\Exchange\Http\Controllers\ExchangeController::class, 'getReceiptData'])->name('receipt_data');
        });
    }
});

/*
|--------------------------------------------------------------------------
| JavaScript Route Configuration
|--------------------------------------------------------------------------
| Update your JavaScript to use these route URLs:
*/

/*
// In your JavaScript file, update these URLs:

// For store operation:
url: '/exchange/store',  // This matches Route::post('/store', ...)

// For print-only receipt:
var receiptUrl = '/exchange/' + exchangeId + '/print-only';  // This matches the new print-only route

// For search transaction:
url: '/exchange/search-transaction',  // This matches your existing route

// For cancel exchange:
url: '/exchange/' + exchangeId + '/cancel',  // This matches Route::post('/{id}/cancel', ...)

// For delete exchange:
url: '/exchange/' + exchangeId,  // This matches Route::delete('/{id}', ...)
method: 'DELETE',
*/

/*
|--------------------------------------------------------------------------
| Controller Method Updates Required
|--------------------------------------------------------------------------
| 
| Make sure your ExchangeController has these methods:
| 
| 1. printReceiptOnly() - for clean receipt printing
| 2. validateExchangeItems() - for enhanced validation
| 3. checkProductStock() - for stock validation
| 4. getReceiptData() - for receipt data API
| 
| These support the bug fixes and enhanced functionality.
*/

/*
|--------------------------------------------------------------------------
| Route Testing Commands
|--------------------------------------------------------------------------
| 
| Test your routes with these commands:
| 
| php artisan route:list --name=exchange
| php artisan route:cache (after changes)
| 
*/