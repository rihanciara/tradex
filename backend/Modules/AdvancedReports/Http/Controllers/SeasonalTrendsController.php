<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Business;
use App\BusinessLocation;
use Carbon\Carbon;

class SeasonalTrendsController extends Controller
{
    /**
     * Display the seasonal trends analysis dashboard
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.seasonal_trends')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        
        // Get business locations for filtering
        $locations = BusinessLocation::forDropdown($business_id, false);
        
        return view('advancedreports::seasonal-trends.index', compact('business', 'locations'));
    }

    /**
     * Get seasonal trends analytics data
     */
    public function analytics(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.seasonal_trends')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        $location_ids = $request->get('location_ids', []);
        $year_range = $request->get('year_range', '2');
        $analysis_type = $request->get('analysis_type', 'revenue');

        try {
            $data = [
                'monthly_trends' => $this->getMonthlyTrends($business_id, $location_ids, $year_range, $analysis_type),
                'yearly_trends' => $this->getYearlyTrends($business_id, $location_ids, $year_range, $analysis_type),
                'holiday_performance' => $this->getHolidayPerformance($business_id, $location_ids, $year_range, $analysis_type),
                'seasonal_patterns' => $this->getSeasonalPatterns($business_id, $location_ids, $year_range, $analysis_type),
                'promotional_effectiveness' => $this->getPromotionalEffectiveness($business_id, $location_ids, $year_range),
                'weather_impact' => $this->getWeatherImpact($business_id, $location_ids, $year_range, $analysis_type),
                'peak_performance' => $this->getPeakPerformance($business_id, $location_ids, $year_range, $analysis_type),
                'trend_summary' => $this->getTrendSummary($business_id, $location_ids, $year_range, $analysis_type),
                'chart_data' => $this->getChartData($business_id, $location_ids, $year_range, $analysis_type)
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error loading seasonal trends data'], 500);
        }
    }

    /**
     * Get monthly trend analysis
     */
    private function getMonthlyTrends($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        $years_back = intval($year_range);
        $start_date = Carbon::now()->subYears($years_back)->startOfYear();
        
        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->select([
                DB::raw('YEAR(t.transaction_date) as year'),
                DB::raw('MONTH(t.transaction_date) as month'),
                DB::raw('MONTHNAME(t.transaction_date) as month_name'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as gross_revenue'),
                DB::raw('SUM(t.discount_amount) as total_discounts'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue'),
                DB::raw('AVG(tsl.quantity * tsl.unit_price_before_discount) as avg_transaction_value')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.transaction_date', '>=', $start_date)
            ->groupBy('year', 'month', 'month_name')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'asc');

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }

        $monthly_data = $query->get();

        // Calculate growth rates and trends
        $trends = [];
        foreach ($monthly_data as $data) {
            $key = $data->year . '-' . str_pad($data->month, 2, '0', STR_PAD_LEFT);
            $trends[$key] = [
                'year' => $data->year,
                'month' => $data->month,
                'month_name' => $data->month_name,
                'transaction_count' => $data->transaction_count,
                'total_quantity' => $data->total_quantity,
                'gross_revenue' => number_format($data->gross_revenue, 2),
                'net_revenue' => number_format($data->net_revenue, 2),
                'total_discounts' => number_format($data->total_discounts, 2),
                'avg_transaction_value' => number_format($data->avg_transaction_value, 2),
                'analysis_value' => $this->getAnalysisValue($data, $analysis_type)
            ];
        }

        // Calculate month-over-month growth
        $previous = null;
        foreach ($trends as $key => &$trend) {
            if ($previous) {
                $current_value = floatval(str_replace(',', '', $trend['analysis_value']));
                $previous_value = floatval(str_replace(',', '', $previous['analysis_value']));
                
                if ($previous_value > 0) {
                    $growth_rate = (($current_value - $previous_value) / $previous_value) * 100;
                    $trend['growth_rate'] = number_format($growth_rate, 2);
                    $trend['growth_direction'] = $growth_rate > 0 ? 'up' : ($growth_rate < 0 ? 'down' : 'stable');
                } else {
                    $trend['growth_rate'] = 'N/A';
                    $trend['growth_direction'] = 'stable';
                }
            }
            $previous = $trend;
        }

        return array_values($trends);
    }

    /**
     * Get yearly trend analysis
     */
    private function getYearlyTrends($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        $years_back = intval($year_range);
        $start_date = Carbon::now()->subYears($years_back)->startOfYear();
        
        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->select([
                DB::raw('YEAR(t.transaction_date) as year'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as gross_revenue'),
                DB::raw('SUM(t.discount_amount) as total_discounts'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue'),
                DB::raw('AVG(tsl.quantity * tsl.unit_price_before_discount) as avg_transaction_value')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.transaction_date', '>=', $start_date)
            ->groupBy('year')
            ->orderBy('year', 'asc');

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }

        $yearly_data = $query->get();

        $trends = [];
        $previous = null;
        
        foreach ($yearly_data as $data) {
            $trend = [
                'year' => $data->year,
                'transaction_count' => $data->transaction_count,
                'total_quantity' => $data->total_quantity,
                'gross_revenue' => number_format($data->gross_revenue, 2),
                'net_revenue' => number_format($data->net_revenue, 2),
                'total_discounts' => number_format($data->total_discounts, 2),
                'avg_transaction_value' => number_format($data->avg_transaction_value, 2),
                'analysis_value' => $this->getAnalysisValue($data, $analysis_type)
            ];

            // Calculate year-over-year growth
            if ($previous) {
                $current_value = floatval(str_replace(',', '', $trend['analysis_value']));
                $previous_value = floatval(str_replace(',', '', $previous['analysis_value']));
                
                if ($previous_value > 0) {
                    $growth_rate = (($current_value - $previous_value) / $previous_value) * 100;
                    $trend['growth_rate'] = number_format($growth_rate, 2);
                    $trend['growth_direction'] = $growth_rate > 0 ? 'up' : ($growth_rate < 0 ? 'down' : 'stable');
                } else {
                    $trend['growth_rate'] = 'N/A';
                    $trend['growth_direction'] = 'stable';
                }
            }

            $trends[] = $trend;
            $previous = $trend;
        }

        return $trends;
    }

    /**
     * Get holiday season performance analysis
     */
    private function getHolidayPerformance($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        // Define holiday periods (customizable based on region)
        $holiday_periods = [
            'New Year' => ['start' => '-12-25', 'end' => '-01-07'],
            'Valentine\'s Day' => ['start' => '-02-10', 'end' => '-02-18'],
            'Easter' => ['start' => '-03-20', 'end' => '-04-20'], // Approximate
            'Summer Season' => ['start' => '-06-01', 'end' => '-08-31'],
            'Halloween' => ['start' => '-10-25', 'end' => '-11-05'],
            'Thanksgiving' => ['start' => '-11-20', 'end' => '-11-30'],
            'Christmas' => ['start' => '-12-15', 'end' => '-12-31']
        ];

        $years_back = intval($year_range);
        $performance_data = [];

        for ($year_offset = 0; $year_offset <= $years_back; $year_offset++) {
            $year = Carbon::now()->subYears($year_offset)->year;
            
            foreach ($holiday_periods as $holiday_name => $period) {
                $start_date = Carbon::createFromFormat('Y-m-d', $year . $period['start']);
                $end_date = Carbon::createFromFormat('Y-m-d', $year . $period['end']);
                
                // Handle cross-year periods (like New Year)
                if ($start_date->month > $end_date->month) {
                    $end_date = $end_date->addYear();
                }

                $query = DB::table('transactions as t')
                    ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                    ->select([
                        DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                        DB::raw('SUM(tsl.quantity) as total_quantity'),
                        DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as gross_revenue'),
                        DB::raw('SUM(t.discount_amount) as total_discounts'),
                        DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue'),
                        DB::raw('AVG(tsl.quantity * tsl.unit_price_before_discount) as avg_transaction_value')
                    ])
                    ->where('t.business_id', $business_id)
                    ->where('t.type', 'sell')
                    ->where('t.status', 'final')
                    ->whereBetween('t.transaction_date', [$start_date, $end_date]);

                if (!empty($location_ids)) {
                    $query->whereIn('t.location_id', $location_ids);
                }

                $holiday_data = $query->first();

                if ($holiday_data && $holiday_data->transaction_count > 0) {
                    $performance_data[] = [
                        'year' => $year,
                        'holiday_name' => $holiday_name,
                        'start_date' => $start_date->format('Y-m-d'),
                        'end_date' => $end_date->format('Y-m-d'),
                        'duration_days' => $start_date->diffInDays($end_date) + 1,
                        'transaction_count' => $holiday_data->transaction_count,
                        'total_quantity' => $holiday_data->total_quantity,
                        'gross_revenue' => number_format($holiday_data->gross_revenue, 2),
                        'net_revenue' => number_format($holiday_data->net_revenue, 2),
                        'total_discounts' => number_format($holiday_data->total_discounts, 2),
                        'avg_transaction_value' => number_format($holiday_data->avg_transaction_value, 2),
                        'daily_average' => number_format($holiday_data->net_revenue / ($start_date->diffInDays($end_date) + 1), 2),
                        'analysis_value' => $this->getAnalysisValue($holiday_data, $analysis_type)
                    ];
                }
            }
        }

        // Sort by year and then by holiday performance
        usort($performance_data, function($a, $b) {
            if ($a['year'] == $b['year']) {
                return floatval(str_replace(',', '', $b['analysis_value'])) <=> floatval(str_replace(',', '', $a['analysis_value']));
            }
            return $b['year'] <=> $a['year'];
        });

        return $performance_data;
    }

    /**
     * Get seasonal patterns analysis
     */
    private function getSeasonalPatterns($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        $seasons = [
            'Spring' => [3, 4, 5],
            'Summer' => [6, 7, 8],
            'Fall' => [9, 10, 11],
            'Winter' => [12, 1, 2]
        ];

        $years_back = intval($year_range);
        $seasonal_data = [];

        foreach ($seasons as $season_name => $months) {
            $query = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->select([
                    DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                    DB::raw('SUM(tsl.quantity) as total_quantity'),
                    DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as gross_revenue'),
                    DB::raw('SUM(t.discount_amount) as total_discounts'),
                    DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue'),
                    DB::raw('AVG(tsl.quantity * tsl.unit_price_before_discount) as avg_transaction_value')
                ])
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('t.transaction_date', '>=', Carbon::now()->subYears($years_back))
                ->whereIn(DB::raw('MONTH(t.transaction_date)'), $months);

            if (!empty($location_ids)) {
                $query->whereIn('t.location_id', $location_ids);
            }

            $season_data = $query->first();

            if ($season_data && $season_data->transaction_count > 0) {
                $seasonal_data[] = [
                    'season' => $season_name,
                    'months' => $months,
                    'transaction_count' => $season_data->transaction_count,
                    'total_quantity' => $season_data->total_quantity,
                    'gross_revenue' => number_format($season_data->gross_revenue, 2),
                    'net_revenue' => number_format($season_data->net_revenue, 2),
                    'total_discounts' => number_format($season_data->total_discounts, 2),
                    'avg_transaction_value' => number_format($season_data->avg_transaction_value, 2),
                    'analysis_value' => $this->getAnalysisValue($season_data, $analysis_type)
                ];
            }
        }

        // Sort by analysis value descending
        usort($seasonal_data, function($a, $b) {
            return floatval(str_replace(',', '', $b['analysis_value'])) <=> floatval(str_replace(',', '', $a['analysis_value']));
        });

        return $seasonal_data;
    }

    /**
     * Get promotional effectiveness analysis
     */
    private function getPromotionalEffectiveness($business_id, $location_ids = [], $year_range = '2')
    {
        $years_back = intval($year_range);
        $start_date = Carbon::now()->subYears($years_back);

        // Compare transactions with and without discounts
        $promotional_query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->select([
                DB::raw('CASE WHEN t.discount_amount > 0 THEN "With Promotion" ELSE "Without Promotion" END as promo_type'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as gross_revenue'),
                DB::raw('SUM(t.discount_amount) as total_discounts'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue'),
                DB::raw('AVG(tsl.quantity * tsl.unit_price_before_discount) as avg_transaction_value'),
                DB::raw('AVG(t.discount_amount) as avg_discount_amount')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.transaction_date', '>=', $start_date)
            ->groupBy(DB::raw('CASE WHEN t.discount_amount > 0 THEN "With Promotion" ELSE "Without Promotion" END'));

        if (!empty($location_ids)) {
            $promotional_query->whereIn('t.location_id', $location_ids);
        }

        $promo_data = $promotional_query->get();

        $effectiveness = [];
        foreach ($promo_data as $data) {
            $effectiveness[] = [
                'type' => $data->promo_type,
                'transaction_count' => $data->transaction_count,
                'total_quantity' => $data->total_quantity,
                'gross_revenue' => number_format($data->gross_revenue, 2),
                'net_revenue' => number_format($data->net_revenue, 2),
                'total_discounts' => number_format($data->total_discounts, 2),
                'avg_transaction_value' => number_format($data->avg_transaction_value, 2),
                'avg_discount_amount' => number_format($data->avg_discount_amount, 2),
                'discount_percentage' => $data->gross_revenue > 0 ? number_format(($data->total_discounts / $data->gross_revenue) * 100, 2) : '0.00'
            ];
        }

        return $effectiveness;
    }

    /**
     * Get weather impact analysis (placeholder for future weather API integration)
     */
    private function getWeatherImpact($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        // This is a placeholder for weather impact analysis
        // In a real implementation, this would integrate with weather APIs
        // to correlate sales data with weather conditions
        
        return [
            'analysis_available' => false,
            'message' => 'Weather impact analysis requires integration with weather API services',
            'recommendation' => 'Consider integrating with services like OpenWeatherMap or WeatherAPI for advanced weather correlation analysis'
        ];
    }

    /**
     * Get peak performance analysis
     */
    private function getPeakPerformance($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        $years_back = intval($year_range);
        $start_date = Carbon::now()->subYears($years_back);

        // Get best performing months
        $best_months = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->select([
                DB::raw('MONTHNAME(t.transaction_date) as month_name'),
                DB::raw('MONTH(t.transaction_date) as month'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.transaction_date', '>=', $start_date)
            ->when(!empty($location_ids), function($query) use ($location_ids) {
                return $query->whereIn('t.location_id', $location_ids);
            })
            ->groupBy('month_name', 'month')
            ->orderBy('net_revenue', 'desc')
            ->limit(3)
            ->get();

        // Get best performing days of week
        $best_days = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->select([
                DB::raw('DAYNAME(t.transaction_date) as day_name'),
                DB::raw('DAYOFWEEK(t.transaction_date) as day_number'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.transaction_date', '>=', $start_date)
            ->when(!empty($location_ids), function($query) use ($location_ids) {
                return $query->whereIn('t.location_id', $location_ids);
            })
            ->groupBy('day_name', 'day_number')
            ->orderBy('net_revenue', 'desc')
            ->get();

        return [
            'best_months' => $best_months->map(function($month) {
                return [
                    'month_name' => $month->month_name,
                    'month' => $month->month,
                    'net_revenue' => number_format($month->net_revenue, 2),
                    'transaction_count' => $month->transaction_count
                ];
            })->toArray(),
            'best_days' => $best_days->map(function($day) {
                return [
                    'day_name' => $day->day_name,
                    'day_number' => $day->day_number,
                    'net_revenue' => number_format($day->net_revenue, 2),
                    'transaction_count' => $day->transaction_count
                ];
            })->toArray()
        ];
    }

    /**
     * Get trend summary statistics
     */
    private function getTrendSummary($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        $years_back = intval($year_range);
        $start_date = Carbon::now()->subYears($years_back);
        $current_year_start = Carbon::now()->startOfYear();
        $last_year_start = Carbon::now()->subYear()->startOfYear();
        $last_year_end = Carbon::now()->subYear()->endOfYear();

        // Current year performance
        $current_year_data = $this->getPeriodPerformance($business_id, $location_ids, $current_year_start, Carbon::now(), $analysis_type);
        
        // Last year performance (same period)
        $last_year_data = $this->getPeriodPerformance($business_id, $location_ids, $last_year_start, $last_year_start->copy()->addDays(Carbon::now()->dayOfYear - 1), $analysis_type);

        // Full last year performance
        $full_last_year_data = $this->getPeriodPerformance($business_id, $location_ids, $last_year_start, $last_year_end, $analysis_type);

        $summary = [
            'current_year' => $current_year_data,
            'last_year_same_period' => $last_year_data,
            'full_last_year' => $full_last_year_data
        ];

        // Calculate year-over-year growth
        if ($last_year_data['net_revenue'] > 0) {
            $yoy_growth = (($current_year_data['net_revenue'] - $last_year_data['net_revenue']) / $last_year_data['net_revenue']) * 100;
            $summary['yoy_growth'] = number_format($yoy_growth, 2);
            $summary['growth_direction'] = $yoy_growth > 0 ? 'up' : ($yoy_growth < 0 ? 'down' : 'stable');
        } else {
            $summary['yoy_growth'] = 'N/A';
            $summary['growth_direction'] = 'stable';
        }

        return $summary;
    }

    /**
     * Get chart data for visualizations
     */
    private function getChartData($business_id, $location_ids = [], $year_range = '2', $analysis_type = 'revenue')
    {
        $monthly_trends = $this->getMonthlyTrends($business_id, $location_ids, $year_range, $analysis_type);
        $seasonal_patterns = $this->getSeasonalPatterns($business_id, $location_ids, $year_range, $analysis_type);
        $peak_performance = $this->getPeakPerformance($business_id, $location_ids, $year_range, $analysis_type);

        return [
            'monthly_trend_chart' => $this->formatMonthlyChartData($monthly_trends),
            'seasonal_chart' => $this->formatSeasonalChartData($seasonal_patterns),
            'day_of_week_chart' => $this->formatDayOfWeekChartData($peak_performance['best_days']),
            'holiday_comparison_chart' => $this->formatHolidayChartData($this->getHolidayPerformance($business_id, $location_ids, $year_range, $analysis_type))
        ];
    }

    /**
     * Export seasonal trends data
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.seasonal_trends')) {
            abort(403, 'Unauthorized action.');
        }

        // Implementation for Excel/CSV export
        // This would generate comprehensive seasonal trends report
        return response()->json(['message' => 'Export functionality to be implemented']);
    }

    /**
     * Helper method to get analysis value based on type
     */
    private function getAnalysisValue($data, $analysis_type)
    {
        switch ($analysis_type) {
            case 'transactions':
                return number_format($data->transaction_count, 0);
            case 'quantity':
                return number_format($data->total_quantity, 0);
            case 'gross_revenue':
                return number_format($data->gross_revenue, 2);
            default: // 'revenue'
                return number_format($data->net_revenue, 2);
        }
    }

    /**
     * Helper method to get period performance
     */
    private function getPeriodPerformance($business_id, $location_ids, $start_date, $end_date, $analysis_type)
    {
        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->select([
                DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as gross_revenue'),
                DB::raw('SUM(t.discount_amount) as total_discounts'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount - COALESCE(t.discount_amount, 0)) as net_revenue')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }

        $data = $query->first();

        return [
            'transaction_count' => $data->transaction_count ?? 0,
            'total_quantity' => $data->total_quantity ?? 0,
            'gross_revenue' => $data->gross_revenue ?? 0,
            'net_revenue' => $data->net_revenue ?? 0,
            'total_discounts' => $data->total_discounts ?? 0
        ];
    }

    /**
     * Format monthly trend data for charts
     */
    private function formatMonthlyChartData($monthly_trends)
    {
        $chart_data = [];
        foreach ($monthly_trends as $trend) {
            $chart_data[] = [
                'label' => $trend['month_name'] . ' ' . $trend['year'],
                'value' => floatval(str_replace(',', '', $trend['analysis_value'])),
                'month' => $trend['month'],
                'year' => $trend['year']
            ];
        }
        return $chart_data;
    }

    /**
     * Format seasonal data for charts
     */
    private function formatSeasonalChartData($seasonal_patterns)
    {
        $colors = [
            'Spring' => '#4CAF50',
            'Summer' => '#FF9800',
            'Fall' => '#795548',
            'Winter' => '#2196F3'
        ];

        $chart_data = [];
        foreach ($seasonal_patterns as $season) {
            $chart_data[] = [
                'label' => $season['season'],
                'value' => floatval(str_replace(',', '', $season['analysis_value'])),
                'color' => $colors[$season['season']] ?? '#9E9E9E'
            ];
        }
        return $chart_data;
    }

    /**
     * Format day of week data for charts
     */
    private function formatDayOfWeekChartData($day_data)
    {
        $chart_data = [];
        foreach ($day_data as $day) {
            $chart_data[] = [
                'label' => $day['day_name'],
                'value' => floatval(str_replace(',', '', $day['net_revenue'])),
                'transactions' => $day['transaction_count']
            ];
        }
        return $chart_data;
    }

    /**
     * Format holiday data for charts
     */
    private function formatHolidayChartData($holiday_data)
    {
        $chart_data = [];
        foreach ($holiday_data as $holiday) {
            $chart_data[] = [
                'label' => $holiday['holiday_name'] . ' ' . $holiday['year'],
                'value' => floatval(str_replace(',', '', $holiday['analysis_value'])),
                'duration' => $holiday['duration_days']
            ];
        }
        return $chart_data;
    }
}