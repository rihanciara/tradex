<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Business;
use App\Transaction;
use App\Contact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class MultiChannelSalesController extends Controller
{
    /**
     * Display the multi-channel sales report dashboard
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $business_id = request()->session()->get('user.business_id');
        $business = Business::find($business_id);

        // Get currency information
        $currency_symbol = session('currency')['symbol'] ?? ($business->currency->symbol ?? '');
        $currency_placement = session('business.currency_symbol_placement') ?? 'before';

        // Get available locations for channel filtering
        $locations = DB::table('business_locations')
                      ->where('business_id', $business_id)
                      ->pluck('name', 'id')
                      ->prepend(__('lang_v1.all'), '');

        // Define channel types
        $channel_types = [
            'all' => __('All Channels'),
            'online' => __('Online'),
            'offline' => __('Offline'),
            'cross_channel' => __('Cross-Channel'),
        ];

        return view('advancedreports::multi-channel.index', compact(
            'business',
            'locations',
            'channel_types',
            'currency_symbol',
            'currency_placement'
        ));
    }

    /**
     * Get channel performance analytics
     */
    public function getChannelPerformance(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $analytics = [
            'overview' => $this->getChannelOverview($business_id, $start_date, $end_date),
            'profitability' => $this->getChannelProfitability($business_id, $start_date, $end_date),
            'customer_behavior' => $this->getCrossChannelBehavior($business_id, $start_date, $end_date),
            'optimization_insights' => $this->getChannelOptimizationInsights($business_id, $start_date, $end_date),
            'trends' => $this->getChannelTrends($business_id, $start_date, $end_date),
        ];

        return response()->json($analytics);
    }

    /**
     * Get channel overview metrics
     */
    private function getChannelOverview($business_id, $start_date, $end_date)
    {
        // Define channel logic based on existing data
        $online_conditions = "t.is_created_from_api = 1 OR t.source IS NOT NULL";
        $offline_conditions = "(t.is_created_from_api = 0 OR t.is_created_from_api IS NULL) AND (t.source IS NULL OR t.source = '')";

        $overview = DB::select("
            SELECT 
                'online' as channel,
                COUNT(DISTINCT t.id) as transactions,
                COUNT(DISTINCT t.contact_id) as customers,
                COALESCE(SUM(t.final_total), 0) as revenue,
                COALESCE(AVG(t.final_total), 0) as avg_order_value
            FROM transactions t
            WHERE t.business_id = ? AND t.type = 'sell' 
            AND t.transaction_date BETWEEN ? AND ?
            AND ({$online_conditions})
            
            UNION ALL
            
            SELECT 
                'offline' as channel,
                COUNT(DISTINCT t.id) as transactions,
                COUNT(DISTINCT t.contact_id) as customers,
                COALESCE(SUM(t.final_total), 0) as revenue,
                COALESCE(AVG(t.final_total), 0) as avg_order_value
            FROM transactions t
            WHERE t.business_id = ? AND t.type = 'sell'
            AND t.transaction_date BETWEEN ? AND ?
            AND ({$offline_conditions})
        ", [$business_id, $start_date, $end_date, $business_id, $start_date, $end_date]);

        return collect($overview)->keyBy('channel');
    }

    /**
     * Get channel profitability analysis
     */
    private function getChannelProfitability($business_id, $start_date, $end_date)
    {
        $profitability = DB::select("
            SELECT 
                CASE 
                    WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN 'online'
                    ELSE 'offline'
                END as channel,
                COUNT(DISTINCT t.id) as total_orders,
                COALESCE(SUM(t.total_before_tax), 0) as gross_revenue,
                COALESCE(SUM(t.final_total), 0) as net_revenue,
                COALESCE(SUM(t.tax_amount), 0) as tax_collected,
                COALESCE(SUM(t.shipping_charges), 0) as shipping_revenue,
                COALESCE(AVG(t.final_total), 0) as avg_order_value,
                COALESCE(SUM(t.discount_amount), 0) as total_discounts
            FROM transactions t
            WHERE t.business_id = ? AND t.type = 'sell'
            AND t.transaction_date BETWEEN ? AND ?
            GROUP BY channel
        ", [$business_id, $start_date, $end_date]);

        return collect($profitability)->keyBy('channel');
    }

    /**
     * Get cross-channel customer behavior
     */
    private function getCrossChannelBehavior($business_id, $start_date, $end_date)
    {
        // Customers with both online and offline transactions
        $cross_channel = DB::select("
            SELECT 
                c.id as customer_id,
                c.name as customer_name,
                COUNT(CASE WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN 1 END) as online_orders,
                COUNT(CASE WHEN (t.is_created_from_api = 0 OR t.is_created_from_api IS NULL) AND (t.source IS NULL OR t.source = '') THEN 1 END) as offline_orders,
                SUM(CASE WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN t.final_total ELSE 0 END) as online_revenue,
                SUM(CASE WHEN (t.is_created_from_api = 0 OR t.is_created_from_api IS NULL) AND (t.source IS NULL OR t.source = '') THEN t.final_total ELSE 0 END) as offline_revenue,
                SUM(t.final_total) as total_revenue
            FROM transactions t
            JOIN contacts c ON t.contact_id = c.id
            WHERE t.business_id = ? AND t.type = 'sell'
            AND t.transaction_date BETWEEN ? AND ?
            GROUP BY c.id, c.name
            HAVING online_orders > 0 AND offline_orders > 0
            ORDER BY total_revenue DESC
            LIMIT 50
        ", [$business_id, $start_date, $end_date]);

        // Channel preference analysis
        $channel_preference = DB::select("
            SELECT 
                COUNT(DISTINCT CASE WHEN online_orders > offline_orders THEN customer_id END) as online_preferred,
                COUNT(DISTINCT CASE WHEN offline_orders > online_orders THEN customer_id END) as offline_preferred,
                COUNT(DISTINCT CASE WHEN online_orders = offline_orders THEN customer_id END) as balanced,
                COUNT(DISTINCT customer_id) as total_cross_channel
            FROM (
                SELECT 
                    c.id as customer_id,
                    COUNT(CASE WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN 1 END) as online_orders,
                    COUNT(CASE WHEN (t.is_created_from_api = 0 OR t.is_created_from_api IS NULL) AND (t.source IS NULL OR t.source = '') THEN 1 END) as offline_orders
                FROM transactions t
                JOIN contacts c ON t.contact_id = c.id
                WHERE t.business_id = ? AND t.type = 'sell'
                AND t.transaction_date BETWEEN ? AND ?
                GROUP BY c.id
                HAVING online_orders > 0 AND offline_orders > 0
            ) as customer_channels
        ", [$business_id, $start_date, $end_date]);

        return [
            'cross_channel_customers' => collect($cross_channel),
            'preference_analysis' => $channel_preference[0] ?? null,
        ];
    }

    /**
     * Get channel optimization insights
     */
    private function getChannelOptimizationInsights($business_id, $start_date, $end_date)
    {
        $insights = [];

        // Peak hours analysis by channel
        $peak_hours = DB::select("
            SELECT 
                HOUR(t.transaction_date) as hour,
                CASE 
                    WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN 'online'
                    ELSE 'offline'
                END as channel,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as revenue
            FROM transactions t
            WHERE t.business_id = ? AND t.type = 'sell'
            AND t.transaction_date BETWEEN ? AND ?
            GROUP BY hour, channel
            ORDER BY hour, channel
        ", [$business_id, $start_date, $end_date]);

        // Channel conversion metrics
        $conversion = DB::select("
            SELECT 
                CASE 
                    WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN 'online'
                    ELSE 'offline'
                END as channel,
                COUNT(*) as total_transactions,
                COUNT(CASE WHEN t.payment_status = 'paid' THEN 1 END) as completed_transactions,
                ROUND((COUNT(CASE WHEN t.payment_status = 'paid' THEN 1 END) * 100.0 / COUNT(*)), 2) as completion_rate
            FROM transactions t
            WHERE t.business_id = ? AND t.type = 'sell'
            AND t.transaction_date BETWEEN ? AND ?
            GROUP BY channel
        ", [$business_id, $start_date, $end_date]);

        // Product performance by channel
        $product_performance = DB::select("
            SELECT 
                p.name as product_name,
                CASE 
                    WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN 'online'
                    ELSE 'offline'
                END as channel,
                SUM(tsl.quantity) as total_quantity,
                SUM(tsl.quantity * tsl.unit_price_inc_tax) as revenue
            FROM transactions t
            JOIN transaction_sell_lines tsl ON t.id = tsl.transaction_id
            JOIN products p ON tsl.product_id = p.id
            WHERE t.business_id = ? AND t.type = 'sell'
            AND t.transaction_date BETWEEN ? AND ?
            GROUP BY p.name, channel
            ORDER BY revenue DESC
            LIMIT 20
        ", [$business_id, $start_date, $end_date]);

        return [
            'peak_hours' => collect($peak_hours)->groupBy('channel'),
            'conversion_metrics' => collect($conversion)->keyBy('channel'),
            'top_products' => collect($product_performance)->groupBy('channel'),
        ];
    }

    /**
     * Get channel trends over time
     */
    private function getChannelTrends($business_id, $start_date, $end_date)
    {
        $trends = DB::select("
            SELECT 
                DATE(t.transaction_date) as date,
                CASE 
                    WHEN t.is_created_from_api = 1 OR t.source IS NOT NULL THEN 'online'
                    ELSE 'offline'
                END as channel,
                COUNT(*) as transactions,
                SUM(t.final_total) as revenue,
                COUNT(DISTINCT t.contact_id) as unique_customers
            FROM transactions t
            WHERE t.business_id = ? AND t.type = 'sell'
            AND t.transaction_date BETWEEN ? AND ?
            GROUP BY DATE(t.transaction_date), channel
            ORDER BY date, channel
        ", [$business_id, $start_date, $end_date]);

        return collect($trends)->groupBy('channel');
    }

    /**
     * Export multi-channel data
     */
    public function export(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $report_type = $request->input('report_type', 'overview');

        $filename = 'multi_channel_' . $report_type . '_' . date('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($business_id, $report_type, $start_date, $end_date) {
            $file = fopen('php://output', 'w');
            
            if ($report_type === 'overview') {
                fputcsv($file, [
                    'Channel',
                    'Transactions',
                    'Customers', 
                    'Revenue',
                    'Avg Order Value',
                    'Conversion Rate'
                ]);

                $overview = $this->getChannelOverview($business_id, $start_date, $end_date);
                foreach ($overview as $channel => $data) {
                    fputcsv($file, [
                        ucfirst($channel),
                        $data->transactions,
                        $data->customers,
                        number_format($data->revenue, 2),
                        number_format($data->avg_order_value, 2),
                        number_format(($data->transactions > 0 ? ($data->customers / $data->transactions) * 100 : 0), 2) . '%'
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}