<?php

/**
 * Advanced Reports Module Index Controller
 * 
 * Main dashboard controller that provides overview of all available reports
 * in the Advanced Reports module. Shows comprehensive business intelligence
 * capabilities with organized report categories and quick access navigation.
 * 
 * @package    AdvancedReports
 * @subpackage Controllers
 * @author     Horizonsoft Solutions
 * @version    1.1.0
 * @since      1.1.0
 */

namespace Modules\AdvancedReports\Http\Controllers;

use App\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Reports Module Index Controller
 * 
 * Provides main dashboard overview of all available reports with
 * organized categories, quick stats, and easy navigation interface
 */
class AdvancedReportsController extends Controller
{
    /**
     * Display the Advanced Reports module main dashboard
     * 
     * Shows comprehensive overview of all available reports organized
     * by business function categories with quick access navigation,
     * basic business stats, and professional interface.
     * 
     * @return \Illuminate\Contracts\View\View
     * @throws \Exception When business not found
     * 
     * @since 1.1.0
     */
    public function index()
    {
        $business_id = session()->get('user.business_id');
        
        // Get business information
        $business = Business::findOrFail($business_id);
        
        // Get quick business stats for dashboard
        $quick_stats = $this->getQuickBusinessStats($business_id);
        
        // Get all available report categories with their reports
        $report_categories = $this->getReportCategories();
        
        return view('advancedreports::index', compact(
            'business',
            'quick_stats', 
            'report_categories'
        ));
    }
    
    /**
     * Get quick business statistics for dashboard overview
     * 
     * @param int $business_id
     * @return array
     */
    private function getQuickBusinessStats($business_id)
    {
        $start_date = Carbon::now()->startOfMonth();
        $end_date = Carbon::now();
        
        try {
            // Basic business metrics
            $total_customers = DB::table('contacts')
                ->where('business_id', $business_id)
                ->where('type', 'customer')
                ->count();
                
            $monthly_sales = DB::table('transactions')
                ->join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereNull('tsl.parent_sell_line_id')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->sum(DB::raw('tsl.quantity * tsl.unit_price_inc_tax'));
                
            $total_products = DB::table('products')
                ->where('business_id', $business_id)
                ->count();
                
            $monthly_transactions = DB::table('transactions')
                ->where('business_id', $business_id)
                ->whereBetween('transaction_date', [$start_date, $end_date])
                ->count();
                
            return [
                'total_customers' => $total_customers,
                'monthly_sales' => $monthly_sales,
                'total_products' => $total_products,
                'monthly_transactions' => $monthly_transactions,
                'period' => $start_date->format('M Y')
            ];
            
        } catch (\Exception $e) {
            \Log::error('Quick stats error: ' . $e->getMessage());
            return [
                'total_customers' => 0,
                'monthly_sales' => 0,
                'total_products' => 0, 
                'monthly_transactions' => 0,
                'period' => $start_date->format('M Y')
            ];
        }
    }
    
    /**
     * Get all report categories with their available reports
     * 
     * @return array
     */
    private function getReportCategories()
    {
        return [
            'customer_analytics' => [
                'title' => 'Customer Analytics & Intelligence',
                'icon' => 'fas fa-users',
                'color' => 'bg-blue',
                'description' => 'Comprehensive customer analysis, segmentation, and lifetime value tracking',
                'reports' => [
                    [
                        'name' => 'Customer Group Performance',
                        'description' => '4-level drill-down analysis with dynamic customer segmentation (VIP, Regular, New)',
                        'route' => 'advancedreports.customer-group-performance.index',
                        'icon' => 'fas fa-chart-line',
                        'featured' => true,
                        'features' => ['Multi-level drill-down', 'Dynamic segmentation', '8 KPI widgets', 'Aging analysis']
                    ],
                    [
                        'name' => 'Customer Behavior Analytics',
                        'description' => 'Purchase patterns, frequency analysis, and behavioral insights for strategic planning',
                        'route' => 'advancedreports.customer-behavior.index',
                        'icon' => 'fas fa-brain',
                        'features' => ['Purchase patterns', 'Behavioral insights', 'Frequency analysis']
                    ],
                    [
                        'name' => 'Customer Lifetime Value (CLV)',
                        'description' => 'RFM segmentation and comprehensive customer value calculation with retention analysis',
                        'route' => 'advancedreports.customer-lifetime-value.index',
                        'icon' => 'fas fa-gem',
                        'features' => ['RFM segmentation', 'CLV calculation', 'Retention metrics']
                    ],
                    [
                        'name' => 'Customer Recognition System',
                        'description' => 'Complete loyalty program management with awards, rankings, and engagement tracking',
                        'route' => 'advancedreports.customer-recognition.index',
                        'icon' => 'fas fa-trophy',
                        'features' => ['Loyalty program', 'Award management', 'Customer rankings']
                    ],
                    [
                        'name' => 'Customer Segmentation Report',
                        'description' => 'Advanced customer classification and detailed demographic analysis',
                        'route' => 'advancedreports.customer-segmentation.index',
                        'icon' => 'fas fa-layer-group',
                        'features' => ['Customer classification', 'Demographic analysis', 'Segment performance']
                    ]
                ]
            ],
            'sales_revenue' => [
                'title' => 'Sales & Revenue Analysis',
                'icon' => 'fas fa-chart-bar',
                'color' => 'bg-green',
                'description' => 'Comprehensive sales performance tracking and revenue optimization analytics',
                'reports' => [
                    [
                        'name' => 'Sales Analytics Dashboard',
                        'description' => 'Comprehensive sales performance tracking with trends, comparisons, and forecasting',
                        'route' => 'advancedreports.sales.index',
                        'icon' => 'fas fa-chart-area',
                        'features' => ['Sales trends', 'Performance comparison', 'Revenue forecasting']
                    ],
                    [
                        'name' => 'Transaction Detail Reports',
                        'description' => 'Granular transaction analysis with advanced filtering and detailed breakdowns',
                        'route' => 'advancedreports.sales-detail.index', 
                        'icon' => 'fas fa-receipt',
                        'features' => ['Transaction details', 'Advanced filtering', 'Payment analysis']
                    ],
                    [
                        'name' => 'Customer Monthly Sales',
                        'description' => 'Period-based customer performance analysis with month-over-month comparisons',
                        'route' => 'advancedreports.customer-monthly.index',
                        'icon' => 'fas fa-calendar-alt',
                        'features' => ['Monthly analysis', 'Customer performance', 'Period comparisons']
                    ],
                    [
                        'name' => 'Product Sales Analysis',
                        'description' => 'Item-wise sales tracking and profitability analysis with performance rankings',
                        'route' => 'advancedreports.itemwise-sales-report.index',
                        'icon' => 'fas fa-boxes',
                        'features' => ['Product performance', 'Profitability tracking', 'Sales rankings']
                    ],
                    [
                        'name' => 'Daily Operations Dashboard',
                        'description' => 'Real-time daily performance metrics with operational insights and alerts',
                        'route' => 'advancedreports.daily-report.index',
                        'icon' => 'fas fa-clock',
                        'features' => ['Daily metrics', 'Real-time insights', 'Operational alerts']
                    ],
                    [
                        'name' => 'Brand Performance Analytics',
                        'description' => 'Brand-wise sales analysis and market share tracking with competitive insights',
                        'route' => 'advancedreports.brand-monthly.index',
                        'icon' => 'fas fa-tags',
                        'features' => ['Brand analysis', 'Market share', 'Competitive insights']
                    ]
                ]
            ],
            'inventory_products' => [
                'title' => 'Inventory & Product Management',
                'icon' => 'fas fa-warehouse',
                'color' => 'bg-orange',
                'description' => 'Stock management, turnover analysis, and product performance optimization',
                'reports' => [
                    [
                        'name' => 'Inventory Turnover Analysis',
                        'description' => 'Stock velocity, turnover ratios, and inventory optimization recommendations',
                        'route' => 'advancedreports.inventory-turnover.index',
                        'icon' => 'fas fa-sync-alt',
                        'featured' => true,
                        'features' => ['Turnover ratios', 'Stock velocity', 'Optimization tips']
                    ],
                    [
                        'name' => 'Demand Forecasting Report',
                        'description' => 'Predictive inventory planning with seasonal analysis and demand patterns',
                        'route' => 'advancedreports.demand-forecasting.index',
                        'icon' => 'fas fa-crystal-ball',
                        'features' => ['Demand prediction', 'Seasonal patterns', 'Inventory planning']
                    ],
                    [
                        'name' => 'Waste & Loss Analysis',
                        'description' => 'Inventory shrinkage tracking and loss prevention with cost impact analysis',
                        'route' => 'advancedreports.waste-loss-analysis.index',
                        'icon' => 'fas fa-exclamation-triangle',
                        'features' => ['Shrinkage tracking', 'Loss prevention', 'Cost impact']
                    ],
                    [
                        'name' => 'Product Category Performance',
                        'description' => 'Category contribution analysis and cross-selling opportunities identification',
                        'route' => 'advancedreports.product-category.index',
                        'icon' => 'fas fa-sitemap',
                        'features' => ['Category analysis', 'Cross-selling', 'Performance metrics']
                    ],
                    [
                        'name' => 'ABC Analysis Dashboard',
                        'description' => 'Product classification based on value and movement with strategic recommendations',
                        'route' => 'advancedreports.abc-analysis.index',
                        'icon' => 'fas fa-sort-amount-up',
                        'features' => ['Product classification', 'Value analysis', 'Strategic insights']
                    ],
                    [
                        'name' => 'Stock Management Reports',
                        'description' => 'Current stock levels, movement tracking, and reorder point analysis',
                        'route' => 'advancedreports.stock.index',
                        'icon' => 'fas fa-clipboard-list',
                        'features' => ['Stock levels', 'Movement tracking', 'Reorder analysis']
                    ]
                ]
            ],
            'financial_compliance' => [
                'title' => 'Financial & Compliance',
                'icon' => 'fas fa-calculator',
                'color' => 'bg-purple',
                'description' => 'Financial analysis, tax compliance, and regulatory reporting solutions',
                'reports' => [
                    [
                        'name' => 'Profit & Loss Analysis',
                        'description' => 'Comprehensive P&L reporting with drill-down capabilities and trend analysis',
                        'route' => 'advancedreports.profit-loss.index',
                        'icon' => 'fas fa-chart-pie',
                        'featured' => true,
                        'features' => ['P&L reporting', 'Trend analysis', 'Drill-down capability']
                    ],
                    [
                        'name' => 'Cash Flow Analysis',
                        'description' => 'Liquidity tracking and cash flow management with predictive insights',
                        'route' => 'advancedreports.cash-flow.index',
                        'icon' => 'fas fa-money-bill-wave',
                        'features' => ['Cash flow tracking', 'Liquidity analysis', 'Predictive insights']
                    ],
                    [
                        'name' => 'Purchase Analysis',
                        'description' => 'Procurement performance and cost optimization with supplier analysis',
                        'route' => 'advancedreports.purchase-analysis.index',
                        'icon' => 'fas fa-shopping-cart',
                        'features' => ['Procurement analysis', 'Cost optimization', 'Supplier insights']
                    ],
                    [
                        'name' => 'Tax Compliance Reports',
                        'description' => 'GST/VAT reporting and regulatory compliance with automated calculations',
                        'route' => 'advancedreports.tax-compliance.index',
                        'icon' => 'fas fa-file-invoice',
                        'features' => ['Tax compliance', 'GST reporting', 'Regulatory tracking']
                    ],
                    [
                        'name' => 'Monthly Expense Reports',
                        'description' => 'Operating expense analysis with category breakdown and budget tracking',
                        'route' => 'advancedreports.expense-monthly.index',
                        'icon' => 'fas fa-credit-card',
                        'features' => ['Expense tracking', 'Category analysis', 'Budget management']
                    ],
                    [
                        'name' => 'Operations Summary',
                        'description' => 'Executive-level business overview with key performance indicators',
                        'route' => 'advancedreports.operations-summary.index',
                        'icon' => 'fas fa-tachometer-alt',
                        'features' => ['Executive overview', 'KPI tracking', 'Performance summary']
                    ]
                ]
            ],
            'performance_recognition' => [
                'title' => 'Performance & Recognition',
                'icon' => 'fas fa-medal',
                'color' => 'bg-red',
                'description' => 'Staff performance tracking, supplier evaluation, and recognition systems',
                'reports' => [
                    [
                        'name' => 'Staff Productivity Reports',
                        'description' => 'Employee performance tracking with productivity metrics and rankings',
                        'route' => 'advancedreports.staff-productivity.index',
                        'icon' => 'fas fa-user-tie',
                        'features' => ['Staff performance', 'Productivity metrics', 'Employee rankings']
                    ],
                    [
                        'name' => 'Location Performance Analysis',
                        'description' => 'Multi-location performance comparison with benchmarking and insights',
                        'route' => 'advancedreports.location-performance.index',
                        'icon' => 'fas fa-map-marker-alt',
                        'features' => ['Location comparison', 'Performance benchmarking', 'Multi-site analysis']
                    ],
                    [
                        'name' => 'Supplier Performance Analysis',
                        'description' => 'Vendor evaluation and performance tracking with delivery and quality metrics',
                        'route' => 'advancedreports.supplier-performance.index',
                        'icon' => 'fas fa-truck',
                        'features' => ['Supplier evaluation', 'Delivery tracking', 'Quality metrics']
                    ],
                    [
                        'name' => 'Service Staff Recognition',
                        'description' => 'Staff reward and recognition system with performance-based incentives',
                        'route' => 'advancedreports.staff-recognition.index',
                        'icon' => 'fas fa-star',
                        'features' => ['Staff recognition', 'Reward system', 'Performance incentives']
                    ],
                    [
                        'name' => 'Warranty & Service Reports',
                        'description' => 'Post-sale service tracking and warranty management with customer satisfaction',
                        'route' => 'advancedreports.warranty-service.index',
                        'icon' => 'fas fa-tools',
                        'features' => ['Warranty tracking', 'Service management', 'Customer satisfaction']
                    ]
                ]
            ],
            'business_intelligence' => [
                'title' => 'Business Intelligence',
                'icon' => 'fas fa-lightbulb',
                'color' => 'bg-teal',
                'description' => 'Advanced analytics, trends analysis, and strategic business insights',
                'reports' => [
                    [
                        'name' => 'Seasonal Trends Analysis',
                        'description' => 'Time-based pattern recognition with seasonal variations and forecasting',
                        'route' => 'advancedreports.seasonal-trends.index',
                        'icon' => 'fas fa-seedling',
                        'features' => ['Seasonal patterns', 'Trend analysis', 'Forecasting']
                    ],
                    [
                        'name' => 'Multi-Channel Sales Report',
                        'description' => 'Omnichannel sales analysis with channel performance and customer journey',
                        'route' => 'advancedreports.multi-channel.index',
                        'icon' => 'fas fa-stream',
                        'features' => ['Channel analysis', 'Omnichannel insights', 'Customer journey']
                    ],
                    [
                        'name' => 'Pricing Optimization',
                        'description' => 'Dynamic pricing recommendations with elasticity analysis and profit optimization',
                        'route' => 'advancedreports.pricing-optimization.index',
                        'icon' => 'fas fa-dollar-sign',
                        'features' => ['Pricing optimization', 'Elasticity analysis', 'Profit maximization']
                    ],
                    [
                        'name' => 'Audit Trail Reports',
                        'description' => 'Activity tracking and compliance monitoring with detailed audit logs',
                        'route' => 'advancedreports.audit-trail.index',
                        'icon' => 'fas fa-search',
                        'features' => ['Activity tracking', 'Compliance monitoring', 'Audit logs']
                    ]
                ]
            ]
        ];
    }
}