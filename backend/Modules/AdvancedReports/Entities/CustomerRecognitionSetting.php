<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;

class CustomerRecognitionSetting extends Model
{
    protected $table = 'customer_recognition_settings';

    protected $fillable = [
        'business_id', 'weekly_enabled', 'monthly_enabled', 'yearly_enabled',
        'winner_count_weekly', 'winner_count_monthly', 'winner_count_yearly',
        'scoring_method', 'sales_weight', 'engagement_weight',
        'module_start_date', 'calculate_historical', 'historical_months', 'is_active'
    ];

    protected $casts = [
        'weekly_enabled' => 'boolean',
        'monthly_enabled' => 'boolean',
        'yearly_enabled' => 'boolean',
        'calculate_historical' => 'boolean',
        'is_active' => 'boolean',
        'module_start_date' => 'date',
        'sales_weight' => 'decimal:2',
        'engagement_weight' => 'decimal:2',
        'winner_count_weekly' => 'integer',
        'winner_count_monthly' => 'integer',
        'winner_count_yearly' => 'integer',
        'historical_months' => 'integer'
    ];

    /**
     * Relationship to Business
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get settings for a specific business
     */
    public static function getForBusiness($business_id)
    {
        return static::where('business_id', $business_id)->first();
    }

    /**
     * Get enabled period types for this business
     */
    public function getEnabledPeriods()
    {
        $periods = [];
        if ($this->weekly_enabled) $periods[] = 'weekly';
        if ($this->monthly_enabled) $periods[] = 'monthly';
        if ($this->yearly_enabled) $periods[] = 'yearly';
        return $periods;
    }

    /**
     * Get winner count for a specific period type
     */
    public function getWinnerCount($period_type)
    {
        switch ($period_type) {
            case 'weekly':
                return $this->winner_count_weekly;
            case 'monthly':
                return $this->winner_count_monthly;
            case 'yearly':
                return $this->winner_count_yearly;
            default:
                return 10;
        }
    }


    /**
     * Initialize settings for a business
     */
    public static function initializeForBusiness($business_id)
    {
        return static::firstOrCreate(
            ['business_id' => $business_id],
            static::getDefaultSettings()
        );
    }

    /**
     * Get summary of current configuration
     */
    public function getConfigurationSummary()
    {
        $enabled_periods = $this->getEnabledPeriods();
        
        return [
            'is_active' => $this->is_active,
            'enabled_periods' => $enabled_periods,
            'enabled_periods_count' => count($enabled_periods),
            'scoring_method' => $this->scoring_method,
            'total_possible_winners' => array_sum([
                $this->weekly_enabled ? $this->winner_count_weekly : 0,
                $this->monthly_enabled ? $this->winner_count_monthly : 0,
                $this->yearly_enabled ? $this->winner_count_yearly : 0
            ]),
            'historical_calculation' => $this->calculate_historical,
            'start_date' => $this->module_start_date
        ];
    }

     /**
     * Get available scoring methods - ADD THIS METHOD
     */
    public static function getScoringMethods()
    {
        return [
            'pure_sales' => __('Pure Sales (Invoice Totals)'),
            'pure_payments' => __('Pure Payments (Money Received)'),
            'weighted' => __('Weighted Sales + Engagement'),
            'weighted_payments' => __('Weighted Payments + Engagement'),
            'payment_adjusted' => __('Payment-Adjusted Sales + Engagement')
        ];
    }

    /**
     * Get scoring method description - ADD THIS METHOD
     */
    public function getScoringMethodDescriptionAttribute()
    {
        $descriptions = [
            'pure_sales' => 'Rankings based only on total invoice amounts (ignores payments)',
            'pure_payments' => 'Rankings based only on actual payments received',
            'weighted' => 'Combines invoice totals with engagement points',
            'weighted_payments' => 'Combines actual payments with engagement points',
            'payment_adjusted' => 'Adjusts invoice totals based on payment percentage, then adds engagement'
        ];

        return $descriptions[$this->scoring_method] ?? 'Unknown scoring method';
    }

    /**
     * Check if scoring method uses payments - ADD THIS METHOD
     */
    public function usesPaymentData()
    {
        return in_array($this->scoring_method, ['pure_payments', 'weighted_payments', 'payment_adjusted']);
    }

    /**
     * Update the existing isWeightedScoring method
     */
    public function isWeightedScoring()
    {
        return in_array($this->scoring_method, ['weighted', 'weighted_payments', 'payment_adjusted']);
    }

    /**
     * Update the existing getScoringWeights method
     */
    public function getScoringWeights()
    {
        if (in_array($this->scoring_method, ['pure_sales', 'pure_payments'])) {
            return [
                'sales_weight' => 1.0,
                'engagement_weight' => 0.0
            ];
        }

        return [
            'sales_weight' => $this->sales_weight,
            'engagement_weight' => $this->engagement_weight
        ];
    }

    /**
     * Update the existing validateWeights method
     */
    public function validateWeights()
    {
        if ($this->isWeightedScoring()) {
            $total = $this->sales_weight + $this->engagement_weight;
            return abs($total - 1.0) < 0.01; // Allow small floating point differences
        }
        return true;
    }

    /**
     * Update the existing getDefaultSettings method
     */
    public static function getDefaultSettings()
    {
        return [
            'weekly_enabled' => true,
            'monthly_enabled' => true,
            'yearly_enabled' => false,
            'winner_count_weekly' => 3,
            'winner_count_monthly' => 10,
            'winner_count_yearly' => 20,
            'scoring_method' => 'payment_adjusted', // Changed default to payment-aware
            'sales_weight' => 0.7,
            'engagement_weight' => 0.3,
            'module_start_date' => now()->format('Y-m-d'),
            'calculate_historical' => false,
            'historical_months' => 12,
            'is_active' => true
        ];
    }
}