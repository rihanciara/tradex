<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])->prefix('jerryupdates')->group(function() {
    Route::get('/', 'JerryUpdatesController@index')->name('jerryupdates.index');
    Route::post('/settings', 'JerryUpdatesController@storeSettings')->name('jerryupdates.settings');
    Route::post('/clear-cache', 'JerryUpdatesController@clearCache')->name('jerryupdates.clear_cache');
    Route::post('/run-accounting-migration', 'JerryUpdatesController@runAccountingMigration')->name('jerryupdates.run_accounting_migration');
    
    // Module installation routes
    Route::get('/install', 'InstallController@index');
    Route::post('/install', 'InstallController@install');
    Route::get('/install/update', 'InstallController@update');
    Route::get('/install/uninstall', 'InstallController@uninstall');

    // Chunked Product Cache Routes
    Route::get('/products/count', 'JerryUpdatesController@getProductCount')->name('jerryupdates.product_count');
    Route::get('/products/chunk', 'JerryUpdatesController@getProductChunk')->name('jerryupdates.product_chunk');
});
