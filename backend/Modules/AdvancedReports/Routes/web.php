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

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->prefix('advanced-reports')->group(function () {
    // Advanced Reports Index/Dashboard
    Route::get('/', 'AdvancedReportsController@index')->name('advancedreports.index');

    // Module Status API endpoints (for proper version display)
    Route::get('/module/version', 'ModuleStatusController@getVersionInfo')->name('advancedreports.module.version');
    Route::get('/module/status', 'ModuleStatusController@getStatus')->name('advancedreports.module.status');

    // Customer Monthly Sales Report
    Route::prefix('customer-monthly-sales')->group(function () {
        Route::get('/', 'CustomerMonthlySalesController@index')->name('advancedreports.customer-monthly.index');
        Route::get('/data', 'CustomerMonthlySalesController@getCustomerMonthlyData')->name('advancedreports.customer-monthly.data');
        Route::get('/summary', 'CustomerMonthlySalesController@getSummary')->name('advancedreports.customer-monthly.summary');
        Route::match(['get', 'post'], '/export', 'CustomerMonthlySalesController@export')->name('advancedreports.customer-monthly.export');
        Route::get('/details/{customerId}', 'CustomerMonthlySalesController@getCustomerDetails')->name('advancedreports.customer-monthly.details');
    });
    // GST Reports
    Route::prefix('gst-sales-report')->group(function () {
        Route::get('/', 'GstSalesReportController@index')->name('advancedreports.gst-sales.index');
        Route::get('/data', 'GstSalesReportController@getGstSalesData')->name('advancedreports.gst-sales.data');
        Route::get('/summary', 'GstSalesReportController@getSummary')->name('advancedreports.gst-sales.summary');
        Route::match(['get', 'post'], '/export', 'GstSalesReportController@export')->name('advancedreports.gst-sales.export');
        Route::get('/per-invoice', 'GstSalesReportController@getGstSalesDataPerInvoice')->name('advancedreports.gst-sales.per-invoice');
    });

    Route::prefix('gst-purchase-report')->group(function () {
        Route::get('/', 'GstPurchaseReportController@index')->name('advancedreports.gst-purchase.index');
        Route::get('/data', 'GstPurchaseReportController@getGstPurchaseData')->name('advancedreports.gst-purchase.data');
        Route::get('/summary', 'GstPurchaseReportController@getSummary')->name('advancedreports.gst-purchase.summary');
        Route::match(['get', 'post'], '/export', 'GstPurchaseReportController@export')->name('advancedreports.gst-purchase.export');
        Route::get('/print', 'GstPurchaseReportController@print')->name('advancedreports.gst-purchase.print'); // NEW PRINT ROUTE
    });
    // Summary Reports
    Route::prefix('operations-summary')->group(function () {
        Route::get('/', 'OperationsSummaryReportController@index')->name('advancedreports.operations-summary.index');
        Route::get('/data', 'OperationsSummaryReportController@getSummaryData')->name('advancedreports.operations-summary.data');
        Route::get('/registers', 'OperationsSummaryReportController@getRegistersData')->name('advancedreports.operations-summary.registers');
        Route::get('/dashboard', 'OperationsSummaryReportController@getDashboardSummary')->name('advancedreports.operations-summary.dashboard');
        Route::match(['get', 'post'], '/export', 'OperationsSummaryReportController@export')->name('advancedreports.operations-summary.export');
    });

    Route::prefix('daily-summary')->group(function () {
        Route::get('/', 'DailySummaryReportController@index')->name('advancedreports.daily-summary.index');
        Route::get('/data', 'DailySummaryReportController@getDailySummaryData')->name('advancedreports.daily-summary.data');
        Route::get('/summary', 'DailySummaryReportController@getSummary')->name('advancedreports.daily-summary.summary');
        Route::get('/details', 'DailySummaryReportController@getDailyDetails')->name('advancedreports.daily-summary.details'); // ADD THIS LINE
        Route::get('/report', 'DailySummaryReportController@dailySummaryReport')->name('advancedreports.daily-summary.report');
        Route::match(['get', 'post'], '/export', 'DailySummaryReportController@export')->name('advancedreports.daily-summary.export');
        Route::get('/details', 'DailySummaryReportController@getDailyDetails');
        Route::get('/purchase', 'DailySummaryReportController@getDailyPurchase');
        Route::get('/purchase-return', 'DailySummaryReportController@getDailyPurchaseReturn');
        Route::get('/purchase-payment', 'DailySummaryReportController@getDailyPurchasePayment');
        Route::get('/sales', 'DailySummaryReportController@getDailySales');
        Route::get('/sale-return', 'DailySummaryReportController@getDailySaleReturn');
        Route::get('/sell-payment', 'DailySummaryReportController@getDailySellPayment');
    });
    Route::get('/daily-sales', 'DailySummaryReportController@getDailySales')->name('daily-sales');

    Route::prefix('daily-report')->group(function () {
        Route::get('/', 'DailyReportController@index')->name('advancedreports.daily-report.index');
        Route::get('/data', 'DailyReportController@getDailyReportData')->name('advancedreports.daily-report.data');
        Route::get('/summary', 'DailyReportController@getSummary')->name('advancedreports.daily-report.summary');
        Route::match(['get', 'post'], '/export', 'DailyReportController@export')->name('advancedreports.daily-report.export');

        // Add these two debug routes:
        Route::get('/test-data', 'DailyReportController@testDataExists')->name('advancedreports.daily-report.test-data');
        Route::get('/debug-detailed', 'DailyReportController@debugDetailedData')->name('advancedreports.daily-report.debug-detailed');
    });

    // Sales Detail Reports
    Route::prefix('sales-detail-report')->group(function () {
        Route::get('/', 'SalesDetailReportController@index')->name('advancedreports.sales-detail.index');
        Route::get('/data', 'SalesDetailReportController@getSalesDetailData')->name('advancedreports.sales-detail.data');
        Route::get('/summary', 'SalesDetailReportController@getWeeklySummary')->name('advancedreports.sales-detail.summary');
        Route::match(['get', 'post'], '/export', 'SalesDetailReportController@export')->name('advancedreports.sales-detail.export');
    });

    // Stock Reports
    Route::prefix('stock-reports')->group(function () {
        Route::get('/', 'StockReportController@index')->name('advancedreports.stock.index');
        Route::get('/data', 'StockReportController@getStockData')->name('advancedreports.stock.data');
        Route::get('/summary', 'StockReportController@getSummary')->name('advancedreports.stock.summary');
        Route::match(['get', 'post'], '/export', 'StockReportController@export')->name('advancedreports.stock.export');
        Route::get('/export-size', 'StockReportController@getExportSize')->name('advancedreports.stock.export-size');
        Route::get('/product-stock-alert', 'StockReportController@getProductStockAlert')->name('advancedreports.stock.product-alert');
        Route::get('/stock-expiry-alert', 'StockReportController@getStockExpiryAlert')->name('advancedreports.stock.expiry-alert');
        // NEW ROUTES FOR STOCK ALERTS AND EXPIRY
        Route::get('/product-stock-alert', 'StockReportController@getProductStockAlert')->name('advancedreports.stock.product-alert');
        Route::get('/stock-expiry-alert', 'StockReportController@getStockExpiryAlert')->name('advancedreports.stock.expiry-alert');
        Route::get('/expiry-alert-summary', 'StockReportController@getExpiryAlertSummary')->name('advancedreports.stock.expiry-alert-summary');
        Route::match(['get', 'post'], '/export-expiry-alert', 'StockReportController@exportExpiryAlert')->name('advancedreports.stock.export-expiry-alert');

        // NEW ROUTES FOR COMPREHENSIVE EXPIRY REPORT
        Route::get('/stock-expiry-report', 'StockReportController@getStockExpiryReport')->name('advancedreports.stock.expiry-report');
        Route::get('/expiry-summary', 'StockReportController@getExpirySummary')->name('advancedreports.stock.expiry-summary');
        Route::match(['get', 'post'], '/export-expiry', 'StockReportController@exportExpiryReport')->name('advancedreports.stock.export-expiry');

        Route::get('/{id}', 'StockReportController@show')->name('advancedreports.stock.show');
    });

    // Sales Reports
    Route::prefix('sales-report')->group(function () {
        Route::get('/', 'SalesReportController@index')->name('advancedreports.sales.index');
        Route::get('/data', 'SalesReportController@getSalesData')->name('advancedreports.sales.data');
        Route::get('/summary', 'SalesReportController@getSummary')->name('advancedreports.sales.summary');
        Route::match(['get', 'post'], '/export', 'SalesReportController@export')->name('advancedreports.sales.export');
        Route::get('/customers', 'SalesReportController@getCustomers')->name('advancedreports.sales.customers');
        Route::get('/{id}', 'SalesReportController@show')->name('advancedreports.sales.show');
    });
    Route::prefix('daily-report')->group(function () {
        Route::get('/', 'DailyReportController@index')->name('advancedreports.daily-report.index');
        Route::get('/data', 'DailyReportController@getDailyReportData')->name('advancedreports.daily-report.data');
        Route::get('/summary', 'DailyReportController@getSummary')->name('advancedreports.daily-report.summary');
        Route::match(['get', 'post'], '/export', 'DailyReportController@export')->name('advancedreports.daily-report.export');

        // Existing debug routes
        Route::get('/test-data', 'DailyReportController@testDataExists')->name('advancedreports.daily-report.test-data');
        Route::get('/debug-detailed', 'DailyReportController@debugDetailedData')->name('advancedreports.daily-report.debug-detailed');

        // ADD THIS NEW ROUTE - Optional separate endpoint for monthly breakdown
        Route::get('/monthly-cash-breakdown', 'DailyReportController@getMonthlyCashBreakdownData')->name('advancedreports.daily-report.monthly-cash');
    });
    // Product Reports - FIXED ORDER
    Route::prefix('product-report')->group(function () {
        Route::get('/', 'ProductReportController@index')->name('advancedreports.product.index');
        Route::get('/data', 'ProductReportController@getProductData')->name('advancedreports.product.data');
        Route::get('/summary', 'ProductReportController@getSummary')->name('advancedreports.product.summary');
        Route::match(['get', 'post'], '/export', 'ProductReportController@export')->name('advancedreports.product.export');

        // SPECIFIC ROUTES FIRST (before catch-all {id})
        Route::get('/weekly-sales', 'ProductReportController@getWeeklySalesReport')->name('advancedreports.product.weekly-sales');
        Route::get('/staff-performance', 'ProductReportController@getStaffPerformanceReport')->name('advancedreports.product.staff-performance');
        Route::get('/purchase-summary', 'ProductReportController@getPurchaseSummaryReport')->name('advancedreports.product.purchase-summary');
        Route::get('/stock-valuation', 'ProductReportController@getStockValuationReport')->name('advancedreports.product.stock-valuation');

        // CATCH-ALL ROUTE LAST
        Route::get('/{id}', 'ProductReportController@show')->name('advancedreports.product.show');
    });

    // Price List - COMMENTED OUT DUE TO MISSING CONTROLLER
    // Route::prefix('price-list')->group(function () {
    //     Route::get('/', 'PriceListController@index')->name('advancedreports.pricelist.index');
    //     Route::get('/data', 'PriceListController@getPriceData')->name('advancedreports.pricelist.data');
    //     Route::get('/export', 'PriceListController@export')->name('advancedreports.pricelist.export');
    //     Route::post('/update-price', 'PriceListController@updatePrice')->name('advancedreports.pricelist.update');
    // });

    // Dashboard/Main - COMMENTED OUT DUE TO MISSING CONTROLLER
    // Route::get('/', 'AdvancedReportsController@index')->name('advancedreports.index');
    // Route::get('/dashboard', 'AdvancedReportsController@dashboard')->name('advancedreports.dashboard');


    // Brand Monthly Sales Report
    Route::prefix('brand-monthly-sales')->group(function () {
        Route::get('/', 'BrandMonthlySalesController@index')->name('advancedreports.brand-monthly.index');
        Route::get('/data', 'BrandMonthlySalesController@getBrandMonthlyData')->name('advancedreports.brand-monthly.data');
        Route::get('/summary', 'BrandMonthlySalesController@getSummary')->name('advancedreports.brand-monthly.summary');
        Route::match(['get', 'post'], '/export', 'BrandMonthlySalesController@export')->name('advancedreports.brand-monthly.export');
        Route::get('/details/{brandId}', 'BrandMonthlySalesController@getBrandDetails')->name('advancedreports.brand-monthly.details');

        Route::get('/brand-wise-sales', 'BrandMonthlySalesController@getBrandWiseReport')->name('advancedreports.brand-monthly.brand-wise-sales');
    });
    Route::prefix('brand-products-sales')->group(function () {
        Route::get('/brand-products', 'BrandMonthlySalesController@getBrandProducts')->name('advancedreports.brand-products.index');
    });

    // Supplier Stock Movement Report - ADD THIS TO YOUR EXISTING web.php
    Route::prefix('supplier-stock-movement')->group(function () {
        Route::get('/', 'SupplierStockMovementController@index')->name('advancedreports.supplier-stock-movement.index');
        Route::get('/data', 'SupplierStockMovementController@getSupplierStockData')->name('advancedreports.supplier-stock-movement.data');
        Route::get('/summary', 'SupplierStockMovementController@getSummary')->name('advancedreports.supplier-stock-movement.summary');
        Route::match(['get', 'post'], '/export', 'SupplierStockMovementController@export')->name('advancedreports.supplier-stock-movement.export');
        Route::get('/details/{supplierId}', 'SupplierStockMovementController@getSupplierDetails')->name('advancedreports.supplier-stock-movement.details');
    });

    // Payment reminder route - COMMENTED OUT DUE TO MISSING CONTROLLER
    // Route::post('/send-payment-reminder', 'WhatsAppController@sendPaymentReminder')->name('whatsapp.sendPaymentReminder');


    // Supplier Monthly Sales Report - ADD THESE ROUTES TO YOUR EXISTING web.php
    Route::prefix('supplier-monthly-sales')->group(function () {
        Route::get('/', 'SupplierMonthlySalesController@index')->name('advancedreports.supplier-monthly.index');
        Route::get('/data', 'SupplierMonthlySalesController@getSupplierMonthlyData')->name('advancedreports.supplier-monthly.data');
        Route::get('/summary', 'SupplierMonthlySalesController@getSummary')->name('advancedreports.supplier-monthly.summary');
        Route::match(['get', 'post'], '/export', 'SupplierMonthlySalesController@export')->name('advancedreports.supplier-monthly.export');
        Route::get('/print', 'SupplierMonthlySalesController@print')->name('advancedreports.supplier-monthly.print');
        Route::get('/details/{supplierId}', 'SupplierMonthlySalesController@getSupplierDetails')->name('advancedreports.supplier-monthly.details');

        Route::get('/supplier-wise-sales', 'SupplierMonthlySalesController@getSupplierWiseReport')->name('advancedreports.supplier-monthly.supplier-wise-sales');
    });
    Route::prefix('supplier-products-sales')->group(function () {
        Route::get('/supplier-products', 'SupplierMonthlySalesController@getSupplierProducts')->name('advancedreports.supplier-products.index');
    });
    // Expense Monthly Report - ADD THIS SECTION
    Route::prefix('expense-monthly')->group(function () {
        Route::get('/', 'ExpenseMonthlyController@index')->name('advancedreports.expense-monthly.index');
        Route::get('/data', 'ExpenseMonthlyController@getExpenseMonthlyData')->name('advancedreports.expense-monthly.data');
        Route::get('/summary', 'ExpenseMonthlyController@getSummary')->name('advancedreports.expense-monthly.summary');
        Route::match(['get', 'post'], '/export', 'ExpenseMonthlyController@export')->name('advancedreports.expense-monthly.export');
        Route::get('/print', 'ExpenseMonthlyController@print')->name('advancedreports.expense-monthly.print');
        Route::get('/details/{categoryId}', 'ExpenseMonthlyController@getCategoryDetails')->name('advancedreports.expense-monthly.details');
    });

    // Itemwise Sales Report
    Route::prefix('itemwise-sales-report')->group(function () {
        Route::get('/', 'ItemwiseSalesReportController@index')->name('advancedreports.itemwise-sales-report.index');
        Route::get('/data', 'ItemwiseSalesReportController@getItemwiseSalesData');
        Route::get('/summary', 'ItemwiseSalesReportController@getSummaryData');
        Route::match(['get', 'post'], '/export', 'ItemwiseSalesReportController@export');
        Route::get('/print', 'ItemwiseSalesReportController@print');
    });


    // Customer Recognition System Routes
    Route::prefix('customer-recognition')->group(function () {
        Route::get('/', 'CustomerRecognitionController@index')->name('advancedreports.customer-recognition.index');
        Route::get('/data', 'CustomerRecognitionController@getCustomerRecognitionData')->name('advancedreports.customer-recognition.data');
        Route::get('/engagements-data', 'CustomerRecognitionController@getEngagementsData')->name('advancedreports.customer-recognition.engagements-data');
        Route::get('/summary', 'CustomerRecognitionController@getSummary')->name('advancedreports.customer-recognition.summary');
        Route::match(['get', 'post'], '/export', 'CustomerRecognitionController@export')->name('advancedreports.customer-recognition.export');
        Route::get('/details/{customerId}', 'CustomerRecognitionController@getCustomerDetails')->name('advancedreports.customer-recognition.details');
        Route::get('/chart-data', 'CustomerRecognitionController@getChartData')->name('advancedreports.customer-recognition.chart-data');

        // Management routes (require customer_recognition.manage permission)
        Route::post('/finalize', 'CustomerRecognitionController@finalizePeriod')->name('advancedreports.customer-recognition.finalize');
        Route::post('/award', 'CustomerRecognitionController@awardCustomer')->name('advancedreports.customer-recognition.award');
        Route::post('/unaward', 'CustomerRecognitionController@unawardCustomer')->name('advancedreports.customer-recognition.unaward');
        Route::post('/record-engagement', 'CustomerRecognitionController@recordEngagement')->name('advancedreports.customer-recognition.record-engagement');
    });

    Route::prefix('award-catalog')->group(function () {
        Route::get('/', 'AwardCatalogController@index')->name('advancedreports.award-catalog.index');
        Route::get('/data', 'AwardCatalogController@getData')->name('advancedreports.award-catalog.data');
        Route::get('/{id}', 'AwardCatalogController@show')->name('advancedreports.award-catalog.show');
        Route::post('/store', 'AwardCatalogController@store')->name('advancedreports.award-catalog.store');
        Route::put('/{id}', 'AwardCatalogController@update')->name('advancedreports.award-catalog.update');
        Route::delete('/{id}', 'AwardCatalogController@destroy')->name('advancedreports.award-catalog.destroy');
        Route::post('/toggle-active', 'AwardCatalogController@toggleActive')->name('advancedreports.award-catalog.toggle-active');
        Route::match(['get', 'post'], '/export', 'AwardCatalogController@export')->name('advancedreports.award-catalog.export');
    });

    // Add these routes to the recognition-settings section:
    Route::prefix('recognition-settings')->group(function () {
        Route::get('/', 'CustomerRecognitionSettingsController@index')->name('advancedreports.recognition-settings.index');
        Route::post('/update', 'CustomerRecognitionSettingsController@update')->name('advancedreports.recognition-settings.update');
        Route::post('/test-scoring', 'CustomerRecognitionSettingsController@testScoring')->name('advancedreports.recognition-settings.test-scoring');
        Route::get('/statistics', 'CustomerRecognitionSettingsController@getStatistics')->name('advancedreports.recognition-settings.statistics');
        Route::post('/reset-data', 'CustomerRecognitionSettingsController@resetData')->name('advancedreports.recognition-settings.reset-data');
        Route::post('/rebuild-cache', 'CustomerRecognitionSettingsController@rebuildCache')->name('advancedreports.recognition-settings.rebuild-cache');
        Route::match(['get', 'post'], '/export-settings', 'CustomerRecognitionSettingsController@exportSettings')->name('advancedreports.recognition-settings.export');
        Route::post('/import-settings', 'CustomerRecognitionSettingsController@importSettings')->name('advancedreports.recognition-settings.import');
    });

    // Service Staff Recognition System Routes
    Route::prefix('staff-recognition')->group(function () {
        Route::get('/', 'StaffRecognitionController@index')->name('advancedreports.staff-recognition.index');
        Route::get('/data', 'StaffRecognitionController@getStaffRecognitionData')->name('advancedreports.staff-recognition.data');
        Route::get('/activities-data', 'StaffRecognitionController@getActivitiesData')->name('advancedreports.staff-recognition.activities-data');
        Route::get('/summary', 'StaffRecognitionController@getSummary')->name('advancedreports.staff-recognition.summary');
        Route::post('/award', 'StaffRecognitionController@awardStaff')->name('advancedreports.staff-recognition.award');
        Route::post('/unaward', 'StaffRecognitionController@unawardStaff')->name('advancedreports.staff-recognition.unaward');
        Route::post('/record-activity', 'StaffRecognitionController@recordActivity')->name('advancedreports.staff-recognition.record-activity');
    });

    // Profit & Loss Analysis Routes
    Route::prefix('profit-loss')->group(function () {
        Route::get('/', 'ProfitLossReportController@index')->name('advancedreports.profit-loss.index');
        Route::get('/data', 'ProfitLossReportController@getProfitLossData')->name('advancedreports.profit-loss.data');
        Route::get('/summary', 'ProfitLossReportController@getSummary')->name('advancedreports.profit-loss.summary');
        Route::get('/analysis', 'ProfitLossReportController@getProfitAnalysis')->name('advancedreports.profit-loss.analysis');
        Route::get('/trends', 'ProfitLossReportController@getProfitTrends')->name('advancedreports.profit-loss.trends');
        Route::match(['get', 'post'], '/export', 'ProfitLossReportController@export')->name('advancedreports.profit-loss.export');
    });

    // Cash Flow Report Routes
    Route::prefix('cash-flow')->group(function () {
        Route::get('/', 'CashFlowReportController@index')->name('advancedreports.cash-flow.index');
        Route::get('/summary', 'CashFlowReportController@getSummary')->name('advancedreports.cash-flow.summary');
        Route::get('/daily-data', 'CashFlowReportController@getDailyCashFlow')->name('advancedreports.cash-flow.daily-data');
        Route::get('/payment-methods', 'CashFlowReportController@getPaymentMethodAnalysis')->name('advancedreports.cash-flow.payment-methods');
        Route::get('/receivables', 'CashFlowReportController@getReceivables')->name('advancedreports.cash-flow.receivables');
        Route::get('/payables', 'CashFlowReportController@getPayables')->name('advancedreports.cash-flow.payables');
        Route::get('/forecast', 'CashFlowReportController@getForecast')->name('advancedreports.cash-flow.forecast');
        Route::match(['get', 'post'], '/export', 'CashFlowReportController@export')->name('advancedreports.cash-flow.export');

        // Test route for debugging
        Route::get('/test-export', function () {
            return response()->json(['message' => 'Cash flow export route is working']);
        });

        // Warranty & Service Report Routes
        Route::prefix('warranty-service')->group(function () {
            Route::get('/', 'WarrantyServiceController@index')->name('advancedreports.warranty-service.index');
            Route::get('/data', 'WarrantyServiceController@getWarrantyServiceData')->name('advancedreports.warranty-service.data');
            Route::match(['get', 'post'], '/export', 'WarrantyServiceController@export')->name('advancedreports.warranty-service.export');
        });
    });

    // Purchase Analysis Report Routes
    Route::prefix('purchase-analysis')->group(function () {
        Route::get('/', 'PurchaseAnalysisController@index')->name('advancedreports.purchase-analysis.index');
        Route::get('/summary', 'PurchaseAnalysisController@getSummary')->name('advancedreports.purchase-analysis.summary');
        Route::get('/supplier-trends', 'PurchaseAnalysisController@getSupplierTrends')->name('advancedreports.purchase-analysis.supplier-trends');
        Route::get('/cost-optimization', 'PurchaseAnalysisController@getCostOptimizationData')->name('advancedreports.purchase-analysis.cost-optimization');
        Route::get('/return-analysis', 'PurchaseAnalysisController@getReturnAnalysisData')->name('advancedreports.purchase-analysis.return-analysis');
        Route::get('/payment-terms', 'PurchaseAnalysisController@getPaymentTermsData')->name('advancedreports.purchase-analysis.payment-terms');
        Route::match(['get', 'post'], '/export', 'PurchaseAnalysisController@export')->name('advancedreports.purchase-analysis.export');
    });

    // Customer Lifetime Value (CLV) Report Routes
    Route::prefix('customer-lifetime-value')->group(function () {
        Route::get('/', 'CustomerLifetimeValueController@index')->name('advancedreports.customer-lifetime-value.index');
        Route::get('/data', 'CustomerLifetimeValueController@getCustomerLifetimeValueData')->name('advancedreports.customer-lifetime-value.data');
        Route::get('/segmentation', 'CustomerLifetimeValueController@getCustomerSegmentationData')->name('advancedreports.customer-lifetime-value.segmentation');
        Route::match(['get', 'post'], '/export', 'CustomerLifetimeValueController@export')->name('advancedreports.customer-lifetime-value.export');
    });

    // Customer Behavior Analytics Routes
    Route::prefix('customer-behavior')->group(function () {
        Route::get('/', 'CustomerBehaviorController@index')->name('advancedreports.customer-behavior.index');
        Route::get('/analytics', 'CustomerBehaviorController@getAnalytics')->name('advancedreports.customer-behavior.analytics');
        Route::match(['get', 'post'], '/export', 'CustomerBehaviorController@export')->name('advancedreports.customer-behavior.export');
        Route::get('/test-export', 'CustomerBehaviorController@testExport')->name('advancedreports.customer-behavior.test-export');
    });

    // Customer Segmentation Report Routes
    Route::prefix('customer-segmentation')->group(function () {
        Route::get('/', 'CustomerSegmentationController@index')->name('advancedreports.customer-segmentation.index');
        Route::get('/analytics', 'CustomerSegmentationController@getAnalytics')->name('advancedreports.customer-segmentation.analytics');
        Route::match(['get', 'post'], '/export', 'CustomerSegmentationController@export')->name('advancedreports.customer-segmentation.export');
    });

    // Inventory Turnover Report Routes
    Route::prefix('inventory-turnover')->group(function () {
        Route::get('/', 'InventoryTurnoverController@index')->name('advancedreports.inventory-turnover.index');
        Route::get('/analytics', 'InventoryTurnoverController@getAnalytics')->name('advancedreports.inventory-turnover.analytics');
        Route::get('/recommendations-data', 'InventoryTurnoverController@getRecommendationsData')->name('advancedreports.inventory-turnover.recommendations-data');
        Route::match(['get', 'post'], '/export', 'InventoryTurnoverController@export')->name('advancedreports.inventory-turnover.export');
    });

    // Demand Forecasting Report Routes
    Route::prefix('demand-forecasting')->group(function () {
        Route::get('/', 'DemandForecastingController@index')->name('advancedreports.demand-forecasting.index');
        Route::get('/analytics', 'DemandForecastingController@getAnalytics')->name('advancedreports.demand-forecasting.analytics');
    });

    // Waste & Loss Analysis Report Routes
    Route::prefix('waste-loss-analysis')->group(function () {
        Route::get('/', 'WasteLossAnalysisController@index')->name('advancedreports.waste-loss.index');
        Route::get('/analytics', 'WasteLossAnalysisController@getAnalytics')->name('advancedreports.waste-loss.analytics');
    });

    // Location Performance Report Routes
    Route::prefix('location-performance')->group(function () {
        Route::get('/', 'LocationPerformanceController@index')->name('advancedreports.location-performance.index');
        Route::get('/analytics', 'LocationPerformanceController@getAnalytics')->name('advancedreports.location-performance.analytics');
    });

    // Staff Productivity Report Routes
    Route::prefix('staff-productivity')->group(function () {
        Route::get('/', 'StaffProductivityController@index')->name('advancedreports.staff-productivity.index');
        Route::get('/analytics', 'StaffProductivityController@getAnalytics')->name('advancedreports.staff-productivity.analytics');
    });

    // Product Category Performance Report Routes
    Route::prefix('product-category')->group(function () {
        Route::get('/', 'ProductCategoryController@index')->name('advancedreports.product-category.index');
        Route::get('/analytics', 'ProductCategoryController@getAnalytics')->name('advancedreports.product-category.analytics');
        Route::match(['get', 'post'], '/export', 'ProductCategoryController@export')->name('advancedreports.product-category.export');
    });

    // Pricing Optimization Report Routes
    Route::prefix('pricing-optimization')->group(function () {
        Route::get('/', 'PricingOptimizationController@index')->name('advancedreports.pricing-optimization.index');
        Route::get('/analytics', 'PricingOptimizationController@analytics')->name('advancedreports.pricing-optimization.analytics');
        Route::match(['get', 'post'], '/export', 'PricingOptimizationController@export')->name('advancedreports.pricing-optimization.export');
    });

    // ABC Analysis Report Routes
    Route::prefix('abc-analysis')->group(function () {
        Route::get('/', 'ABCAnalysisController@index')->name('advancedreports.abc-analysis.index');
        Route::get('/analytics', 'ABCAnalysisController@analytics')->name('advancedreports.abc-analysis.analytics');
        Route::match(['get', 'post'], '/export', 'ABCAnalysisController@export')->name('advancedreports.abc-analysis.export');
    });

    // Seasonal Trends Report Routes
    Route::prefix('seasonal-trends')->group(function () {
        Route::get('/', 'SeasonalTrendsController@index')->name('advancedreports.seasonal-trends.index');
        Route::get('/analytics', 'SeasonalTrendsController@analytics')->name('advancedreports.seasonal-trends.analytics');
        Route::match(['get', 'post'], '/export', 'SeasonalTrendsController@export')->name('advancedreports.seasonal-trends.export');
    });

    // Audit Trail Report Routes
    Route::prefix('audit-trail')->group(function () {
        Route::get('/', 'AuditTrailController@index')->name('advancedreports.audit-trail.index');
        Route::get('/data', 'AuditTrailController@getAuditData')->name('advancedreports.audit-trail.data');
        Route::get('/summary', 'AuditTrailController@getSummary')->name('advancedreports.audit-trail.summary');
        Route::match(['get', 'post'], '/export', 'AuditTrailController@export')->name('advancedreports.audit-trail.export');
    });

    // Tax Compliance Report Routes
    Route::prefix('tax-compliance')->group(function () {
        Route::get('/', 'TaxComplianceController@index')->name('advancedreports.tax-compliance.index');
        Route::get('/summary', 'TaxComplianceController@getSummary')->name('advancedreports.tax-compliance.summary');
        Route::get('/liability-details', 'TaxComplianceController@getTaxLiabilityDetails')->name('advancedreports.tax-compliance.liability-details');
        Route::get('/filing-assistance', 'TaxComplianceController@getFilingAssistance')->name('advancedreports.tax-compliance.filing-assistance');
        Route::get('/optimization', 'TaxComplianceController@getTaxOptimizationInsights')->name('advancedreports.tax-compliance.optimization');
        Route::match(['get', 'post'], '/export', 'TaxComplianceController@export')->name('advancedreports.tax-compliance.export');
    });

    // Multi-Channel Sales Report Routes
    Route::prefix('multi-channel')->group(function () {
        Route::get('/', 'MultiChannelSalesController@index')->name('advancedreports.multi-channel.index');
        Route::get('/performance', 'MultiChannelSalesController@getChannelPerformance')->name('advancedreports.multi-channel.performance');
        Route::match(['get', 'post'], '/export', 'MultiChannelSalesController@export')->name('advancedreports.multi-channel.export');
    });

    // Supplier Performance Report Routes
    Route::prefix('supplier-performance')->group(function () {
        Route::get('/', 'SupplierPerformanceController@index')->name('advancedreports.supplier-performance.index');
        Route::get('/data', 'SupplierPerformanceController@getSupplierPerformanceData')->name('advancedreports.supplier-performance.data');
        Route::match(['get', 'post'], '/export', 'SupplierPerformanceController@export')->name('advancedreports.supplier-performance.export');
    });

    // Customer Group Performance Report Routes
    Route::prefix('customer-group-performance')->group(function () {
        Route::get('/', 'CustomerGroupController@index')->name('advancedreports.customer-group-performance.index');
        Route::get('/data', 'CustomerGroupController@getCustomerGroupData')->name('advancedreports.customer-group-performance.data');
        Route::get('/salespeople/{customer_group}', 'CustomerGroupController@getSalespersonDrilldown')->name('advancedreports.customer-group-performance.salespeople');
        Route::get('/customers/{salesperson_id}', 'CustomerGroupController@getCustomerDrilldown')->name('advancedreports.customer-group-performance.customers');
        Route::get('/invoices/{customer_id}', 'CustomerGroupController@getInvoiceDrilldown')->name('advancedreports.customer-group-performance.invoices');
        Route::match(['get', 'post'], '/export', 'CustomerGroupController@export')->name('advancedreports.customer-group-performance.export');
    });

    // Business Analytics Dashboard Report Routes (Admin only - using can check instead)
    Route::prefix('business-analytics')->group(function () {
        Route::get('/', 'BusinessAnalyticsController@index')->name('advancedreports.business-analytics.index');
        Route::get('/data', 'BusinessAnalyticsController@getBusinessAnalyticsData')->name('advancedreports.business-analytics.data');
        Route::get('/summary', 'BusinessAnalyticsController@getSummary')->name('advancedreports.business-analytics.summary');
        Route::match(['get', 'post'], '/export', 'BusinessAnalyticsController@export')->name('advancedreports.business-analytics.export');
    });

    // Reward Points Tracking & Liability Report Routes
    Route::prefix('reward-points')->group(function () {
        Route::get('/', 'RewardPointsController@index')->name('advancedreports.reward-points.index');
        Route::get('/summary', 'RewardPointsController@getSummaryData')->name('advancedreports.reward-points.summary');
        Route::get('/customer-summary', 'RewardPointsController@getCustomerSummary')->name('advancedreports.reward-points.customer-summary');
        Route::get('/transaction-details', 'RewardPointsController@getTransactionDetails')->name('advancedreports.reward-points.transaction-details');
        Route::get('/top-performers', 'RewardPointsController@getTopPerformers')->name('advancedreports.reward-points.top-performers');
        Route::match(['get', 'post'], '/export', 'RewardPointsController@export')->name('advancedreports.reward-points.export');
    });
});

// Install & Update Routes
Route::middleware('web', 'authh', 'auth')->group(function () {
    Route::get('advanced-reports/install', 'InstallController@index');
    Route::get('advanced-reports/update', 'InstallController@update');
    Route::get('advanced-reports/uninstall', 'InstallController@uninstall');
});