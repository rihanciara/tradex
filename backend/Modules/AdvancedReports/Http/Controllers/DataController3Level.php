<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use Menu;

class DataController extends Controller
{
    /**
     * Defines module as a superadmin package.
     * @return Array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'advanced_reports_module',
                'label' => __('advancedreports::lang.advanced_reports_module'),
                'default' => false,
            ]
        ];
    }

    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'AdvancedReports.view',
                'label' => __('advancedreports::lang.view_reports'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.stock_report',
                'label' => __('advancedreports::lang.stock_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.sales_report',
                'label' => __('advancedreports::lang.sales_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.product_report',
                'label' => __('advancedreports::lang.product_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.gst_sales_report',
                'label' => __('advancedreports::lang.gst_sales_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.gst_purchase_report',
                'label' => __('advancedreports::lang.gst_purchase_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.operations_summary_report',
                'label' => __('advancedreports::lang.operations_summary_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.daily_summary_report',
                'label' => __('advancedreports::lang.daily_summary_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.daily_report',
                'label' => __('advancedreports::lang.daily_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.export',
                'label' => __('advancedreports::lang.export_reports'),
                'default' => false
            ],
        ];
    }

    /**
     * Modify admin menu to add AdvancedReports menu items
     */
public function modifyAdminMenu()
{
    $business_id = session()->get('user.business_id');
    $module_util = new ModuleUtil();
    
    $is_advanced_reports_enabled = (bool)$module_util->hasThePermissionInSubscription($business_id, 'advanced_reports_module', 'superadmin_package');

    if ($is_advanced_reports_enabled && auth()->user()->can('AdvancedReports.view')) {
        $menuparent = Menu::instance('admin-sidebar-menu');

        $menuparent->dropdown(
            __('advancedreports::lang.advanced_reports'),
            function ($main) {
                
                // Weekly Reports
                $main->dropdown(__('Weekly Reports'), function ($weekly) {
                    $weekly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'index']) . '?period=week',
                        __('Sales Report'),
                        ['icon' => '', 'active' => request()->segment(2) == 'sales-report' && request()->get('period') == 'week']
                    );
                    $weekly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'index']) . '?period=week&type=purchase',
                        __('Purchase Report'),
                        ['icon' => '', 'active' => request()->segment(2) == 'product-report' && request()->get('type') == 'purchase']
                    );
                    $weekly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ExpenseMonthlyController::class, 'index']) . '?period=week',
                        __('Expense Report'),
                        ['icon' => '', 'active' => request()->segment(2) == 'expense-monthly' && request()->get('period') == 'week']
                    );
                    $weekly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'index']) . '?period=week&group_by=location',
                        __('Locations Report'),
                        ['icon' => '', 'active' => request()->get('group_by') == 'location']
                    );
                    $weekly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'getStaffPerformanceReport']) . '?period=week',
                        __('Staff Report'),
                        ['icon' => '', 'active' => request()->routeIs('advancedreports.product.staff-performance')]
                    );
                    $weekly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\StockReportController::class, 'index']) . '?period=week&type=transfer',
                        __('Stock Transfer Report'),
                        ['icon' => '', 'active' => request()->get('type') == 'transfer']
                    );
                    $weekly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\StockReportController::class, 'index']) . '?period=week&type=adjustment',
                        __('Wastage / Adjustment Report'),
                        ['icon' => '', 'active' => request()->get('type') == 'adjustment']
                    );
                }, ['icon' => 'fas fa-calendar-week']);

                // Monthly Reports
                $main->dropdown(__('Monthly Reports'), function ($monthly) {
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'getPurchaseSummaryReport']) . '?period=month',
                        __('Purchase Report'),
                        ['icon' => '', 'active' => request()->routeIs('advancedreports.product.purchase-summary')]
                    );
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'index']) . '?period=month',
                        __('Sales Report'),
                        ['icon' => '', 'active' => request()->segment(2) == 'sales-report' && request()->get('period') == 'month']
                    );
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'index']) . '?comparison=purchase_vs_sales',
                        __('Purchase Vs Sales'),
                        ['icon' => '', 'active' => request()->get('comparison') == 'purchase_vs_sales']
                    );
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ExpenseMonthlyController::class, 'index']),
                        __('Expense Report'),
                        ['icon' => '', 'active' => request()->segment(2) == 'expense-monthly']
                    );
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'index']) . '?period=month&group_by=location',
                        __('Locations Report'),
                        ['icon' => '', 'active' => request()->get('group_by') == 'location']
                    );
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\CustomerMonthlySalesController::class, 'index']),
                        __('Customer Report'),
                        ['icon' => '', 'active' => request()->segment(2) == 'customer-monthly-sales']
                    );
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\BrandMonthlySalesController::class, 'index']),
                        __('Brand & Category Report'),
                        ['icon' => '', 'active' => request()->routeIs('advancedreports.brand-monthly.index')]
                    );
                    $monthly->url(
                        action([\Modules\AdvancedReports\Http\Controllers\SupplierMonthlySalesController::class, 'index']),
                        __('Sales Report (Supplier)'),
                        ['icon' => '', 'active' => request()->routeIs('advancedreports.supplier-monthly.index')]
                    );
                }, ['icon' => 'fas fa-calendar-alt']);

                // Daily Reports
                if (auth()->user()->can('AdvancedReports.daily_report') || auth()->user()->can('AdvancedReports.daily_summary_report')) {
                    $main->dropdown(__('Daily Reports'), function ($daily) {
                        if (auth()->user()->can('AdvancedReports.daily_report')) {
                            $daily->url(
                                action([\Modules\AdvancedReports\Http\Controllers\DailyReportController::class, 'index']),
                                __('Report (Comprehensive Overview)'),
                                ['icon' => '', 'active' => request()->segment(2) == 'daily-report']
                            );
                        }
                        
                        if (auth()->user()->can('AdvancedReports.daily_summary_report')) {
                            $daily->url(
                                action([\Modules\AdvancedReports\Http\Controllers\DailySummaryReportController::class, 'index']),
                                __('Summary Report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'daily-summary']
                            );
                        }
                        
                        $daily->url(
                            action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'index']) . '?period=day',
                            __('Sales Report (Invoice & Payment)'),
                            ['icon' => '', 'active' => request()->segment(2) == 'sales-report' && request()->get('period') == 'day']
                        );
                        
                        $daily->url(
                            action([\Modules\AdvancedReports\Http\Controllers\SalesDetailReportController::class, 'index']),
                            __('Product Sales Detail Report'),
                            ['icon' => '', 'active' => request()->segment(2) == 'sales-detail-report']
                        );
                        
                        $daily->url(
                            action([\Modules\AdvancedReports\Http\Controllers\BrandMonthlySalesController::class, 'getBrandWiseReport']),
                            __('Sales Report (Brands & Categories)'),
                            ['icon' => '', 'active' => request()->routeIs('advancedreports.brand-monthly.brand-wise-sales')]
                        );
                        
                        $daily->url(
                            action([\Modules\AdvancedReports\Http\Controllers\ItemwiseSalesReportController::class, 'index']),
                            __('Item Wise Sales Report'),
                            ['icon' => '', 'active' => request()->segment(2) == 'itemwise-sales-report']
                        );
                    }, ['icon' => 'fas fa-calendar-day']);
                }

                // Financial & Tax Reports
                $main->dropdown(__('Financial & Tax Reports'), function ($financial) {
                    if (auth()->user()->can('AdvancedReports.gst_sales_report')) {
                        $financial->url(
                            action([\Modules\AdvancedReports\Http\Controllers\GstSalesReportController::class, 'index']),
                            __('GST Sales Report'),
                            ['icon' => '', 'active' => request()->segment(2) == 'gst-sales-report']
                        );
                    }

                    if (auth()->user()->can('AdvancedReports.gst_purchase_report')) {
                        $financial->url(
                            action([\Modules\AdvancedReports\Http\Controllers\GstPurchaseReportController::class, 'index']),
                            __('GST Purchase Report'),
                            ['icon' => '', 'active' => request()->segment(2) == 'gst-purchase-report']
                        );
                    }

                    if (auth()->user()->can('AdvancedReports.operations_summary_report')) {
                        $financial->url(
                            action([\Modules\AdvancedReports\Http\Controllers\OperationsSummaryReportController::class, 'index']),
                            __('Operational Summary Report'),
                            ['icon' => '', 'active' => request()->segment(2) == 'operations-summary']
                        );
                    }

                    $financial->url(
                        action([\Modules\AdvancedReports\Http\Controllers\DailyReportController::class, 'getMonthlyCashBreakdownData']),
                        __('Profit & Loss Report'),
                        ['icon' => '', 'active' => request()->routeIs('advancedreports.daily-report.monthly-cash')]
                    );
                    
                    $financial->url(
                        action([\Modules\AdvancedReports\Http\Controllers\DailyReportController::class, 'index']) . '?view=cash_flow',
                        __('Cash Flow Report'),
                        ['icon' => '', 'active' => request()->get('view') == 'cash_flow']
                    );
                    
                    $financial->url(
                        action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'index']) . '?payment_status=due',
                        __('Account Receivable (Customer Due)'),
                        ['icon' => '', 'active' => request()->get('payment_status') == 'due']
                    );
                    
                    $financial->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'index']) . '?payment_status=due&type=purchase',
                        __('Account Payable (Supplier Due)'),
                        ['icon' => '', 'active' => request()->get('payment_status') == 'due' && request()->get('type') == 'purchase']
                    );
                }, ['icon' => 'fas fa-chart-line']);

                // Advanced Analytics (New grouping for existing advanced reports)
                $main->dropdown(__('Advanced Analytics'), function ($analytics) {
                    $analytics->url(
                        action([\Modules\AdvancedReports\Http\Controllers\StockReportController::class, 'index']),
                        __('Stock Reports'),
                        ['icon' => '', 'active' => request()->segment(2) == 'stock-reports']
                    );

                    $analytics->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'index']),
                        __('Product Performance Analysis'),
                        ['icon' => '', 'active' => request()->segment(2) == 'product-report']
                    );

                    $analytics->url(
                        action([\Modules\AdvancedReports\Http\Controllers\SupplierStockMovementController::class, 'index']),
                        __('Supplier Stock Movement & Profit'),
                        ['icon' => '', 'active' => request()->segment(2) == 'supplier-stock-movement']
                    );

                    $analytics->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'getStockValuationReport']),
                        __('Stock Valuation Report'),
                        ['icon' => '', 'active' => request()->routeIs('advancedreports.product.stock-valuation')]
                    );

                    $analytics->url(
                        action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'getWeeklySalesReport']),
                        __('Sales Analysis'),
                        ['icon' => '', 'active' => request()->routeIs('advancedreports.product.weekly-sales')]
                    );

                    // Customer Recognition System
                    if (auth()->user()->can('AdvancedReports.customer_recognition')) {
                        $analytics->url(
                            action([\Modules\AdvancedReports\Http\Controllers\CustomerRecognitionController::class, 'index']),
                            __('Customer Recognition System'),
                            ['icon' => '', 'active' => request()->segment(2) == 'customer-recognition']
                        );
                    }
                }, ['icon' => 'fas fa-chart-area']);
            },
            [
                'icon' => 'fas fa-chart-bar',
                'active' => request()->segment(1) == 'advanced-reports'
            ]
        )->order(25);
    }
}
}