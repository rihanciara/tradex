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
            // General Access
            [
                'value' => 'AdvancedReports.view',
                'label' => __('advancedreports::lang.view_reports'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.export',
                'label' => __('advancedreports::lang.export_reports'),
                'default' => false
            ],

            // Sales & Revenue Reports
            [
                'value' => 'AdvancedReports.sales_report',
                'label' => __('advancedreports::lang.sales_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.sales_detail_report',
                'label' => __('advancedreports::lang.sales_detail_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.customer_monthly_sales',
                'label' => __('Customer Monthly Sales Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.itemwise_sales_report',
                'label' => __('Itemwise Sales Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.daily_report',
                'label' => __('advancedreports::lang.daily_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.daily_summary_report',
                'label' => __('advancedreports::lang.daily_summary_report'),
                'default' => false
            ],

            // Product & Inventory Reports
            [
                'value' => 'AdvancedReports.inventory_turnover',
                'label' => __('Inventory Turnover Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.demand_forecasting',
                'label' => __('Demand Forecasting Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.waste_loss_analysis',
                'label' => __('Waste & Loss Analysis Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.location_performance',
                'label' => __('Location Performance Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.staff_productivity',
                'label' => __('Staff Productivity Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.product_category_performance',
                'label' => __('Product Category Performance'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.stock_report',
                'label' => __('advancedreports::lang.stock_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.product_report',
                'label' => __('advancedreports::lang.product_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.brand_monthly_sales',
                'label' => __('Brand Monthly Sales Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.brand_wise_sales',
                'label' => __('Brand Wise Sales Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.supplier_monthly_sales',
                'label' => __('Supplier Monthly Sales Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.supplier_wise_sales',
                'label' => __('Supplier Wise Sales Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.supplier_stock_movement',
                'label' => __('Supplier Stock Movement & Profit'),
                'default' => false
            ],

            // Financial & Tax Reports
            [
                'value' => 'AdvancedReports.profit_loss_report',
                'label' => __('Profit & Loss Analysis'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.cash_flow_report',
                'label' => __('Cash Flow Analysis'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.purchase_analysis_report',
                'label' => __('Purchase Analysis'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.customer_lifetime_value',
                'label' => __('Customer Lifetime Value (CLV)'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.customer_behavior',
                'label' => __('Customer Behavior Analytics'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.customer_segmentation',
                'label' => __('Customer Segmentation Report'),
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
                'value' => 'AdvancedReports.expense_monthly_report',
                'label' => __('Expense Monthly Report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.operations_summary_report',
                'label' => __('advancedreports::lang.operations_summary_report'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.customer_group_performance',
                'label' => __('advancedreports::lang.customer_group_report'),
                'default' => false
            ],

            // Recognition & Staff Management
            [
                'value' => 'AdvancedReports.customer_recognition_system',
                'label' => __('advancedreports::lang.customer_recognition_system'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.service_staff_recognition_system',
                'label' => __('advancedreports::lang.service_staff_recognition_system'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.pricing_optimization',
                'label' => __('advancedreports::lang.pricing_optimization'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.abc_analysis',
                'label' => __('advancedreports::lang.abc_analysis'),
                'default' => false
            ],
            [
                'value' => 'AdvancedReports.seasonal_trends',
                'label' => __('advancedreports::lang.seasonal_trends_report'),
                'default' => false
            ],

            // Audit Trail Report (Compliance & Risk)
            [
                'value' => 'AdvancedReports.audit_trail',
                'label' => __('advancedreports::lang.audit_trail_report'),
                'default' => false
            ],

            // Tax Compliance Report
            [
                'value' => 'AdvancedReports.tax_compliance',
                'label' => __('advancedreports::lang.tax_compliance_report'),
                'default' => false
            ],

            // Multi-Channel Sales Report
            [
                'value' => 'AdvancedReports.multi_channel_sales',
                'label' => __('advancedreports::lang.multi_channel_sales_report'),
                'default' => false
            ],

            // Supplier Performance Report
            [
                'value' => 'AdvancedReports.supplier_performance',
                'label' => __('advancedreports::lang.supplier_performance_report'),
                'default' => false
            ],

            // Warranty & Service Report
            [
                'value' => 'AdvancedReports.warranty_service',
                'label' => __('advancedreports::lang.warranty_service_report'),
                'default' => false
            ],

            // Reward Points Tracking & Liability Report
            [
                'value' => 'AdvancedReports.reward_points',
                'label' => __('Reward Points Tracking & Liability Report'),
                'default' => false
            ],
        ];
    }

    /**
     * Modify admin menu to add AdvancedReports menu items
     */
    public function modifyAdminMenu()
    {
        try {
            // Check if module is actually installed (has version in system table)
            $is_installed = \App\System::getProperty('advancedreports_version');
            if (empty($is_installed)) {
                return; // Module not installed, skip menu modification
            }

            // Check if routes are available - if action() fails, routes aren't loaded
            if (!function_exists('action') || !class_exists('\Modules\AdvancedReports\Http\Controllers\AdvancedReportsController')) {
                return; // Routes not loaded properly, skip menu
            }

            $business_id = session()->get('user.business_id');
            $module_util = new ModuleUtil();

            // Fixed: Changed module name to match superadmin_package definition
            $is_advanced_reports_enabled = (bool)$module_util->hasThePermissionInSubscription($business_id, 'advanced_reports_module', 'superadmin_package');

            // Fixed: Simplified permission check - removed 'superadmin' check that was blocking regular admins
            if ($is_advanced_reports_enabled && auth()->user()->can('AdvancedReports.view')) {
                $menuparent = Menu::instance('admin-sidebar-menu');

                $menuparent->dropdown(
                    __('advancedreports::lang.advanced_reports'),
                    function ($sub) {
                        // Main Dashboard
                        $sub->url(
                            action([\Modules\AdvancedReports\Http\Controllers\AdvancedReportsController::class, 'index']),
                            __('advancedreports::lang.dashboard'),
                            ['icon' => '', 'active' => request()->segment(2) == '' && request()->segment(1) == 'advanced-reports']
                        );

                        // Sales & Revenue Reports
                        if (auth()->user()->can('AdvancedReports.sales_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'index']),
                                __('advancedreports::lang.sales_analytics'),
                                ['icon' => '', 'active' => request()->segment(2) == 'sales-report']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.sales_detail_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\SalesDetailReportController::class, 'index']),
                                __('advancedreports::lang.transaction_details'),
                                ['icon' => '', 'active' => request()->segment(2) == 'sales-detail-report']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.customer_monthly_sales')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\CustomerMonthlySalesController::class, 'index']),
                                __('advancedreports::lang.customer_performance'),
                                ['icon' => '', 'active' => request()->segment(2) == 'customer-monthly-sales']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.itemwise_sales_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\ItemwiseSalesReportController::class, 'index']),
                                __('advancedreports::lang.product_sales_analysis'),
                                ['icon' => '', 'active' => request()->segment(2) == 'itemwise-sales-report']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.daily_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\DailyReportController::class, 'index']),
                                __('advancedreports::lang.daily_operations'),
                                ['icon' => '', 'active' => request()->segment(2) == 'daily-report']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.daily_summary_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\DailySummaryReportController::class, 'index']),
                                __('advancedreports::lang.daily_dashboard'),
                                ['icon' => '', 'active' => request()->segment(2) == 'daily-summary']
                            );
                        }

                        // Inventory & Product Reports

                        if (auth()->user()->can('AdvancedReports.inventory_turnover')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\InventoryTurnoverController::class, 'index']),
                                __('advancedreports::lang.inventory_turnover_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'inventory-turnover']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.demand_forecasting')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\DemandForecastingController::class, 'index']),
                                __('advancedreports::lang.demand_forecasting_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'demand-forecasting']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.waste_loss_analysis')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\WasteLossAnalysisController::class, 'index']),
                                __('advancedreports::lang.waste_loss_analysis_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'waste-loss-analysis']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.location_performance')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\LocationPerformanceController::class, 'index']),
                                __('advancedreports::lang.location_performance_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'location-performance']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.staff_productivity')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\StaffProductivityController::class, 'index']),
                                __('advancedreports::lang.staff_productivity_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'staff-productivity']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.product_category_performance')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\ProductCategoryController::class, 'index']),
                                __('advancedreports::lang.product_category_performance'),
                                ['active' => request()->segment(2) == 'product-category']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.pricing_optimization')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\PricingOptimizationController::class, 'index']),
                                __('advancedreports::lang.pricing_optimization'),
                                ['active' => request()->segment(2) == 'pricing-optimization']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.abc_analysis')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\ABCAnalysisController::class, 'index']),
                                __('advancedreports::lang.abc_analysis'),
                                ['active' => request()->segment(2) == 'abc-analysis']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.seasonal_trends')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\SeasonalTrendsController::class, 'index']),
                                __('advancedreports::lang.seasonal_trends_report'),
                                ['active' => request()->segment(2) == 'seasonal-trends']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.audit_trail')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\AuditTrailController::class, 'index']),
                                __('advancedreports::lang.audit_trail_report'),
                                ['active' => request()->segment(2) == 'audit-trail']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.tax_compliance')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\TaxComplianceController::class, 'index']),
                                __('advancedreports::lang.tax_compliance_report'),
                                ['active' => request()->segment(2) == 'tax-compliance']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.multi_channel_sales')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\MultiChannelSalesController::class, 'index']),
                                __('advancedreports::lang.multi_channel_sales_report'),
                                ['active' => request()->segment(2) == 'multi-channel']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.supplier_performance')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\SupplierPerformanceController::class, 'index']),
                                __('advancedreports::lang.supplier_performance_report'),
                                ['active' => request()->segment(2) == 'supplier-performance']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.warranty_service')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\WarrantyServiceController::class, 'index']),
                                __('advancedreports::lang.warranty_service_report'),
                                ['active' => request()->segment(2) == 'warranty-service']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.stock_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\StockReportController::class, 'index']),
                                __('advancedreports::lang.stock_management'),
                                ['active' => request()->route() && request()->route()->getName() == 'advancedreports.stock.index']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.product_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\ProductReportController::class, 'index']),
                                __('advancedreports::lang.product_performance'),
                                ['active' => request()->segment(2) == 'product-report']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.brand_monthly_sales')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\BrandMonthlySalesController::class, 'index']),
                                __('advancedreports::lang.brand_analytics'),
                                ['active' => request()->routeIs('advancedreports.brand-monthly.index')]
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.brand_wise_sales')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\BrandMonthlySalesController::class, 'getBrandWiseReport']),
                                __('advancedreports::lang.brand_comparison'),
                                ['active' => request()->routeIs('advancedreports.brand-monthly.brand-wise-sales')]
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.supplier_monthly_sales')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\SupplierMonthlySalesController::class, 'index']),
                                __('advancedreports::lang.supplier_performance'),
                                ['active' => request()->routeIs('advancedreports.supplier-monthly.index')]
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.supplier_wise_sales')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\SupplierMonthlySalesController::class, 'getSupplierWiseReport']),
                                __('advancedreports::lang.supplier_analysis'),
                                ['active' => request()->routeIs('advancedreports.supplier-monthly.supplier-wise-sales')]
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.supplier_stock_movement')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\SupplierStockMovementController::class, 'index']),
                                __('advancedreports::lang.supplier_profitability'),
                                ['active' => request()->segment(2) == 'supplier-stock-movement']
                            );
                        }

                        // FINANCIAL & COMPLIANCE
                        $sub->divider();
                        $sub->header('<b>💰 ' . __('advancedreports::lang.financial_compliance') . '</b>');

                        if (auth()->user()->can('AdvancedReports.profit_loss_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\ProfitLossReportController::class, 'index']),
                                __('advancedreports::lang.profit_loss_analysis'),
                                ['active' => request()->segment(2) == 'profit-loss']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.cash_flow_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\CashFlowReportController::class, 'index']),
                                __('advancedreports::lang.cash_flow_analysis'),
                                ['active' => request()->segment(2) == 'cash-flow']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.purchase_analysis_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\PurchaseAnalysisController::class, 'index']),
                                __('advancedreports::lang.purchase_analysis'),
                                ['active' => request()->segment(2) == 'purchase-analysis']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.customer_lifetime_value')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\CustomerLifetimeValueController::class, 'index']),
                                __('advancedreports::lang.customer_lifetime_value'),
                                ['active' => request()->segment(2) == 'customer-lifetime-value']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.customer_behavior')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\CustomerBehaviorController::class, 'index']),
                                __('advancedreports::lang.customer_behavior_analytics'),
                                ['active' => request()->segment(2) == 'customer-behavior']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.customer_segmentation')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\CustomerSegmentationController::class, 'index']),
                                __('advancedreports::lang.customer_segmentation_report'),
                                ['active' => request()->segment(2) == 'customer-segmentation']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.gst_sales_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\GstSalesReportController::class, 'index']),
                                __('advancedreports::lang.gst_sales_compliance'),
                                ['active' => request()->segment(2) == 'gst-sales-report']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.gst_purchase_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\GstPurchaseReportController::class, 'index']),
                                __('advancedreports::lang.gst_purchase_compliance'),
                                ['active' => request()->segment(2) == 'gst-purchase-report']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.expense_monthly_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\ExpenseMonthlyController::class, 'index']),
                                __('advancedreports::lang.monthly_expenses'),
                                ['active' => request()->segment(2) == 'expense-monthly']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.operations_summary_report')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\OperationsSummaryReportController::class, 'index']),
                                __('advancedreports::lang.business_operations_summary'),
                                ['active' => request()->segment(2) == 'operations-summary']
                            );
                        }

                        // CUSTOMER & STAFF ENGAGEMENT
                        $sub->divider();
                        $sub->header('<b>🏆 ' . __('advancedreports::lang.customer_staff_engagement') . '</b>');

                        if (auth()->user()->can('AdvancedReports.customer_group_performance')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\CustomerGroupController::class, 'index']),
                                __('advancedreports::lang.customer_group_report'),
                                ['active' => request()->segment(2) == 'customer-group-performance']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.customer_recognition_system')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\CustomerRecognitionController::class, 'index']),
                                __('advancedreports::lang.customer_loyalty_program'),
                                ['active' => request()->segment(2) == 'customer-recognition']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.service_staff_recognition_system')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\StaffRecognitionController::class, 'index']),
                                __('advancedreports::lang.staff_performance_management'),
                                ['active' => request()->segment(2) == 'staff-recognition']
                            );
                        }

                        if (auth()->user()->can('AdvancedReports.reward_points')) {
                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\RewardPointsController::class, 'index']),
                                'Reward Points Tracking & Liability',
                                ['active' => request()->segment(2) == 'reward-points']
                            );
                        }

                        // ADMIN & SYSTEM ANALYTICS (Superadmin only)
                        $administrator_list = config('constants.administrator_usernames');
                        $is_superadmin = !empty(auth()->user()) &&
                            in_array(strtolower(auth()->user()->username), explode(',', strtolower($administrator_list)));

                        if ($is_superadmin) {
                            $sub->divider();
                            $sub->header('<b>⚙️ ' . __('System Analytics') . '</b>');

                            $sub->url(
                                action([\Modules\AdvancedReports\Http\Controllers\BusinessAnalyticsController::class, 'index']),
                                'Business Analytics Dashboard',
                                ['active' => request()->segment(2) == 'business-analytics']
                            );
                        }
                    },
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                    <path d="M9 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                    <path d="M15 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                    <path d="M4 20l14 0" />
                    </svg>',
                        'active' => request()->segment(1) == 'advanced-reports'
                    ]
                )->order(25);
            }
        } catch (\Exception $e) {
            // Silently fail if there's any error in menu modification
            // This prevents the entire page from breaking
            \Log::warning('AdvancedReports: Failed to modify admin menu: ' . $e->getMessage());
            return;
        }
    }
}