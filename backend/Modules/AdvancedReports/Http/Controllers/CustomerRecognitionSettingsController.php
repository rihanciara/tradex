<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdvancedReports\Entities\CustomerRecognitionSetting;
use Modules\AdvancedReports\Utils\CustomerRecognitionUtil;
use Carbon\Carbon;

class CustomerRecognitionSettingsController extends Controller
{
/**
 * Display settings page
 */
public function index()
{
    if (!auth()->user()->can('customer_recognition.manage')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');
    
    // Get or create settings
    $settings = CustomerRecognitionUtil::initializeBusinessSettings($business_id);

    // Get dropdown options for the form
    $scoring_methods = CustomerRecognitionSetting::getScoringMethods();
    
    $period_types = [
        'weekly' => __('Weekly'),
        'monthly' => __('Monthly'), 
        'yearly' => __('Yearly')
    ];

    return view('advancedreports::recognition-settings.index')
        ->with(compact('settings', 'scoring_methods', 'period_types'));
}

/**
 * Update settings
 */
public function update(Request $request)
{
    if (!auth()->user()->can('customer_recognition.manage')) {
        abort(403, 'Unauthorized action.');
    }

    // Updated validation to include new scoring methods
    $request->validate([
        'weekly_enabled' => 'boolean',
        'monthly_enabled' => 'boolean',
        'yearly_enabled' => 'boolean',
        'winner_count_weekly' => 'required|integer|min:1|max:50',
        'winner_count_monthly' => 'required|integer|min:1|max:100',
        'winner_count_yearly' => 'required|integer|min:1|max:200',
        'scoring_method' => 'required|in:' . implode(',', array_keys(CustomerRecognitionSetting::getScoringMethods())),
        'sales_weight' => 'required_if:scoring_method,weighted,weighted_payments,payment_adjusted|numeric|min:0|max:1',
        'engagement_weight' => 'required_if:scoring_method,weighted,weighted_payments,payment_adjusted|numeric|min:0|max:1',
        'module_start_date' => 'required|date',
        'calculate_historical' => 'boolean',
        'historical_months' => 'required_if:calculate_historical,true|integer|min:1|max:60',
        'is_active' => 'boolean'
    ]);

    try {
        $business_id = $request->session()->get('user.business_id');

        $settings = CustomerRecognitionSetting::where('business_id', $business_id)->first();

        if (!$settings) {
            $settings = CustomerRecognitionUtil::initializeBusinessSettings($business_id);
        }

        // Validate weights sum to 1.0 for weighted scoring methods
        if (in_array($request->scoring_method, ['weighted', 'weighted_payments', 'payment_adjusted'])) {
            $sales_weight = $request->sales_weight;
            $engagement_weight = $request->engagement_weight;
            
            if (abs(($sales_weight + $engagement_weight) - 1.0) > 0.01) {
                return response()->json([
                    'error' => 'Sales weight and engagement weight must sum to 1.0 (100%)'
                ], 422);
            }
        }

        $data = $request->only([
            'weekly_enabled', 'monthly_enabled', 'yearly_enabled',
            'winner_count_weekly', 'winner_count_monthly', 'winner_count_yearly',
            'scoring_method', 'sales_weight', 'engagement_weight',
            'module_start_date', 'calculate_historical', 'historical_months',
            'is_active'
        ]);

        // Convert checkboxes
        $data['weekly_enabled'] = $request->has('weekly_enabled');
        $data['monthly_enabled'] = $request->has('monthly_enabled');
        $data['yearly_enabled'] = $request->has('yearly_enabled');
        $data['calculate_historical'] = $request->has('calculate_historical');
        $data['is_active'] = $request->has('is_active');

        // Set default weights for pure methods
        if (in_array($data['scoring_method'], ['pure_sales', 'pure_payments'])) {
            $data['sales_weight'] = 1.0;
            $data['engagement_weight'] = 0.0;
        }

        $settings->update($data);

        // If historical calculation is enabled and start date changed, recalculate
        if ($data['calculate_historical'] && $request->has('recalculate_historical')) {
            $this->recalculateHistoricalData($business_id, $settings);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'settings' => $settings,
            'uses_payment_data' => $settings->usesPaymentData() // Add this info
        ]);

    } catch (\Exception $e) {
        \Log::error('Recognition Settings Update Error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

/**
 * Test scoring calculation
 */
public function testScoring(Request $request)
{
    if (!auth()->user()->can('customer_recognition.manage')) {
        abort(403, 'Unauthorized action.');
    }

    $request->validate([
        'sales_value' => 'required|numeric|min:0',
        'total_paid' => 'nullable|numeric|min:0', // NEW: for payment methods
        'engagement_points' => 'required|integer|min:0',
        'scoring_method' => 'required|in:' . implode(',', array_keys(CustomerRecognitionSetting::getScoringMethods())),
        'sales_weight' => 'nullable|numeric|min:0|max:1',
        'engagement_weight' => 'nullable|numeric|min:0|max:1'
    ]);

    try {
        $sales_value = $request->sales_value;
        $total_paid = $request->total_paid ?: $sales_value; // Default to full payment
        $engagement_points = $request->engagement_points;
        $scoring_method = $request->scoring_method;
        $sales_weight = $request->sales_weight ?: 0.7;
        $engagement_weight = $request->engagement_weight ?: 0.3;

        // Calculate payment percentage
        $payment_percentage = $sales_value > 0 ? ($total_paid / $sales_value) * 100 : 100;

        switch ($scoring_method) {
            case 'pure_sales':
                $final_score = $sales_value;
                $calculation = "Pure Sales: $" . number_format($sales_value, 2);
                break;

            case 'pure_payments':
                $final_score = $total_paid;
                $calculation = "Pure Payments: $" . number_format($total_paid, 2);
                break;

            case 'weighted':
                $sales_score = $sales_value * $sales_weight;
                $engagement_score = ($engagement_points * 10) * $engagement_weight;
                $final_score = $sales_score + $engagement_score;
                
                $calculation = sprintf(
                    "Weighted: ($%.2f × %.2f) + (%d pts × 10 × %.2f) = $%.2f + $%.2f = $%.2f",
                    $sales_value, $sales_weight,
                    $engagement_points, $engagement_weight,
                    $sales_score, $engagement_score, $final_score
                );
                break;

            case 'weighted_payments':
                $payment_score = $total_paid * $sales_weight;
                $engagement_score = ($engagement_points * 10) * $engagement_weight;
                $final_score = $payment_score + $engagement_score;
                
                $calculation = sprintf(
                    "Weighted Payments: ($%.2f × %.2f) + (%d pts × 10 × %.2f) = $%.2f + $%.2f = $%.2f",
                    $total_paid, $sales_weight,
                    $engagement_points, $engagement_weight,
                    $payment_score, $engagement_score, $final_score
                );
                break;

            case 'payment_adjusted':
                $payment_factor = max(0.1, $payment_percentage / 100); // Minimum 10% credit
                $adjusted_sales = $sales_value * $payment_factor;
                $sales_score = $adjusted_sales * $sales_weight;
                $engagement_score = ($engagement_points * 10) * $engagement_weight;
                $final_score = $sales_score + $engagement_score;
                
                $calculation = sprintf(
                    "Payment Adjusted: ($%.2f × %.1f%% × %.2f) + (%d pts × 10 × %.2f) = $%.2f + $%.2f = $%.2f",
                    $sales_value, $payment_percentage, $sales_weight,
                    $engagement_points, $engagement_weight,
                    $sales_score, $engagement_score, $final_score
                );
                break;

            default:
                throw new \Exception('Unknown scoring method');
        }

        return response()->json([
            'success' => true,
            'final_score' => $final_score,
            'calculation' => $calculation,
            'components' => [
                'sales_component' => isset($sales_score) ? $sales_score : (isset($payment_score) ? $payment_score : $final_score),
                'engagement_component' => isset($engagement_score) ? $engagement_score : 0,
                'payment_percentage' => $payment_percentage
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * Get current statistics
     */
    public function getStatistics(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            
            // Get cache statistics
            $cache_stats = \App\CustomerRecognitionCache::where('business_id', $business_id)
                ->selectRaw('
                    period_type,
                    COUNT(*) as total_customers,
                    SUM(sales_total) as total_sales,
                    SUM(engagement_points) as total_engagement,
                    AVG(final_score) as avg_score,
                    MAX(final_score) as top_score
                ')
                ->groupBy('period_type')
                ->get()
                ->keyBy('period_type');

            // Get award statistics
            $award_stats = \App\CustomerAward::where('business_id', $business_id)
                ->selectRaw('
                    period_type,
                    COUNT(*) as total_awards,
                    COUNT(CASE WHEN is_awarded = 1 THEN 1 END) as awarded_count,
                    SUM(gift_monetary_value) as total_award_value
                ')
                ->groupBy('period_type')
                ->get()
                ->keyBy('period_type');

            // Get engagement statistics
            $engagement_stats = \App\CustomerEngagement::where('business_id', $business_id)
                ->where('status', 'verified')
                ->selectRaw('
                    engagement_type,
                    COUNT(*) as count,
                    SUM(points) as total_points,
                    AVG(points) as avg_points
                ')
                ->groupBy('engagement_type')
                ->get()
                ->keyBy('engagement_type');

            return response()->json([
                'success' => true,
                'cache_stats' => $cache_stats,
                'award_stats' => $award_stats,
                'engagement_stats' => $engagement_stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Recognition Statistics Error: ' . $e->getMessage());
            return response()->json(['error' => 'Statistics loading failed'], 500);
        }
    }

    /**
     * Reset all data (dangerous operation)
     */
    public function resetData(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'confirmation' => 'required|in:RESET_ALL_DATA',
            'reset_type' => 'required|in:cache_only,awards_only,engagements_only,everything'
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            $reset_type = $request->reset_type;

            \DB::beginTransaction();

            switch ($reset_type) {
                case 'cache_only':
                    \App\CustomerRecognitionCache::where('business_id', $business_id)->delete();
                    $message = 'Recognition cache cleared successfully';
                    break;

                case 'awards_only':
                    \App\CustomerAward::where('business_id', $business_id)->delete();
                    \App\AwardPeriod::where('business_id', $business_id)->delete();
                    $message = 'All awards and periods cleared successfully';
                    break;

                case 'engagements_only':
                    \App\CustomerEngagement::where('business_id', $business_id)->delete();
                    $message = 'All engagement records cleared successfully';
                    break;

                case 'everything':
                    \App\CustomerRecognitionCache::where('business_id', $business_id)->delete();
                    \App\CustomerAward::where('business_id', $business_id)->delete();
                    \App\AwardPeriod::where('business_id', $business_id)->delete();
                    \App\CustomerEngagement::where('business_id', $business_id)->delete();
                    $message = 'All recognition data cleared successfully';
                    break;
            }

            \DB::commit();

            \Log::warning('Customer Recognition Data Reset', [
                'business_id' => $business_id,
                'reset_type' => $reset_type,
                'user_id' => auth()->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Recognition Reset Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Rebuild cache for all periods
     */
    public function rebuildCache(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $settings = CustomerRecognitionSetting::getForBusiness($business_id);

            if (!$settings || !$settings->is_active) {
                return response()->json(['error' => 'Recognition system not active'], 400);
            }

            $periods_rebuilt = 0;
            $enabled_periods = $settings->getEnabledPeriods();

            foreach ($enabled_periods as $period_type) {
                // Get current period dates
                $dates = \App\AwardPeriod::getPeriodDates($period_type);
                
                // Rebuild cache for current period
                CustomerRecognitionUtil::updateCacheForPeriod(
                    $business_id,
                    $period_type,
                    $dates['start'],
                    $dates['end']
                );
                
                $periods_rebuilt++;

                // Also rebuild previous periods if historical calculation is enabled
                if ($settings->calculate_historical) {
                    for ($i = 1; $i <= min(12, $settings->historical_months); $i++) {
                        $past_date = Carbon::now();
                        
                        if ($period_type === 'weekly') {
                            $past_date->subWeeks($i);
                        } elseif ($period_type === 'monthly') {
                            $past_date->subMonths($i);
                        } else {
                            $past_date->subYears($i);
                        }
                        
                        $past_dates = \App\AwardPeriod::getPeriodDates($period_type, $past_date);
                        
                        CustomerRecognitionUtil::updateCacheForPeriod(
                            $business_id,
                            $period_type,
                            $past_dates['start'],
                            $past_dates['end']
                        );
                        
                        $periods_rebuilt++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Cache rebuilt for {$periods_rebuilt} periods successfully"
            ]);

        } catch (\Exception $e) {
            \Log::error('Recognition Cache Rebuild Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Recalculate historical data
     */
    private function recalculateHistoricalData($business_id, $settings)
    {
        if (!$settings->calculate_historical) {
            return;
        }

        $enabled_periods = $settings->getEnabledPeriods();
        $start_date = Carbon::parse($settings->module_start_date);
        $months_back = min($settings->historical_months, 60); // Cap at 60 months

        foreach ($enabled_periods as $period_type) {
            for ($i = 0; $i < $months_back; $i++) {
                $date = $start_date->copy();
                
                if ($period_type === 'weekly') {
                    $date->addWeeks($i);
                } elseif ($period_type === 'monthly') {
                    $date->addMonths($i);
                } else {
                    $date->addYears($i);
                }
                
                if ($date->isFuture()) {
                    break;
                }
                
                $dates = \App\AwardPeriod::getPeriodDates($period_type, $date);
                
                CustomerRecognitionUtil::updateCacheForPeriod(
                    $business_id,
                    $period_type,
                    $dates['start'],
                    $dates['end']
                );
            }
        }
    }

    /**
     * Export settings configuration
     */
    public function exportSettings(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $settings = CustomerRecognitionSetting::getForBusiness($business_id);

            if (!$settings) {
                return response()->json(['error' => 'No settings found'], 404);
            }

            $export_data = [
                'settings' => $settings->toArray(),
                'export_date' => now()->toDateTimeString(),
                'business_id' => $business_id,
                'version' => '1.0'
            ];

            $filename = 'customer_recognition_settings_' . date('Y_m_d_H_i_s') . '.json';

            return response()->json($export_data)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            \Log::error('Settings Export Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Import settings configuration
     */
    public function importSettings(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:1024'
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            $file = $request->file('settings_file');
            
            $json_content = file_get_contents($file->getPathname());
            $import_data = json_decode($json_content, true);

            if (!$import_data || !isset($import_data['settings'])) {
                return response()->json(['error' => 'Invalid settings file format'], 400);
            }

            $settings_data = $import_data['settings'];
            
            // Remove IDs and timestamps
            unset($settings_data['id'], $settings_data['created_at'], $settings_data['updated_at']);
            
            // Ensure business_id is correct
            $settings_data['business_id'] = $business_id;

            $settings = CustomerRecognitionSetting::where('business_id', $business_id)->first();
            
            if ($settings) {
                $settings->update($settings_data);
            } else {
                CustomerRecognitionSetting::create($settings_data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings imported successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Settings Import Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}