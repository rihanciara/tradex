<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\Contact;
use App\User;

class CustomerAward extends Model
{
    protected $table = 'customer_awards';

    protected $fillable = [
        'business_id', 'customer_id', 'period_id', 'period_type',
        'period_start', 'period_end', 'rank_position', 'sales_total',
        'engagement_points', 'final_score', 'transaction_count',
        'avg_transaction_value', 'award_type', 'catalog_item_id', 'award_quantity',
        'gift_description', 'gift_monetary_value', 'stock_deducted',
        'award_notes', 'is_awarded', 'awarded_by', 'awarded_date',
        'notification_sent', 'certificate_path'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'sales_total' => 'decimal:4',
        'final_score' => 'decimal:4',
        'avg_transaction_value' => 'decimal:4',
        'gift_monetary_value' => 'decimal:4',
        'stock_deducted' => 'boolean',
        'is_awarded' => 'boolean',
        'awarded_date' => 'datetime',
        'notification_sent' => 'boolean',
        'rank_position' => 'integer',
        'engagement_points' => 'integer',
        'transaction_count' => 'integer'
    ];

    /**
     * Relationship to Business
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relationship to Customer (Contact)
     */
    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    /**
     * Relationship to AwardPeriod
     */
    public function period()
    {
        return $this->belongsTo(AwardPeriod::class, 'period_id');
    }

    /**
     * Relationship to Product Variation (for catalog awards)
     */
    public function productVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'catalog_item_id');
    }

    /**
     * Relationship to User who awarded
     */
    public function awardedBy()
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    /**
     * Get top customers for a period
     */
    public static function getTopCustomers($business_id, $period_type, $period_start, $period_end, $limit = 10)
    {
        return static::where('business_id', $business_id)
            ->where('period_type', $period_type)
            ->where('period_start', $period_start)
            ->where('period_end', $period_end)
            ->orderBy('final_score', 'desc')
            ->orderBy('sales_total', 'desc')
            ->limit($limit)
            ->with(['customer', 'catalogItem'])
            ->get();
    }

    /**
     * Get rank suffix for display (1st, 2nd, 3rd, etc.)
     */
    public function getRankSuffixAttribute()
    {
        $rank = $this->rank_position;
        
        if ($rank % 100 >= 11 && $rank % 100 <= 13) {
            return $rank . 'th';
        }
        
        switch ($rank % 10) {
            case 1: return $rank . 'st';
            case 2: return $rank . 'nd';
            case 3: return $rank . 'rd';
            default: return $rank . 'th';
        }
    }

    /**
     * Get award display name
     */
    public function getAwardDisplayNameAttribute()
    {
        if ($this->award_type === 'catalog' && $this->catalogItem) {
            return $this->catalogItem->award_name;
        }
        
        return $this->gift_description ?: 'Recognition Award';
    }

    /**
     * Get customer display name
     */
    public function getCustomerDisplayNameAttribute()
    {
        if (!$this->customer) {
            return 'Unknown Customer';
        }
        
        if ($this->customer->supplier_business_name) {
            return $this->customer->supplier_business_name . ' - ' . $this->customer->name;
        }
        
        return $this->customer->name;
    }

    /**
     * Get period label for display
     */
    public function getPeriodDisplayAttribute()
    {
        return ucfirst($this->period_type) . ' (' . 
               $this->period_start->format('M d') . ' - ' . 
               $this->period_end->format('M d, Y') . ')';
    }

    /**
     * Check if award is a top 3 winner
     */
    public function getIsTopThreeAttribute()
    {
        return $this->rank_position <= 3;
    }

    /**
     * Get rank color for display
     */
    public function getRankColorAttribute()
    {
        switch ($this->rank_position) {
            case 1: return 'gold';
            case 2: return 'silver';
            case 3: return 'bronze';
            default: return 'default';
        }
    }

    /**
     * Get rank icon
     */
    public function getRankIconAttribute()
    {
        switch ($this->rank_position) {
            case 1: return 'fa-trophy';
            case 2: return 'fa-medal';
            case 3: return 'fa-award';
            default: return 'fa-star';
        }
    }

    /**
     * Award the customer
     */
    public function awardCustomer($award_data, $user_id)
    {
        // Update award information
        $this->update([
            'award_type' => $award_data['award_type'],
            'gift_description' => $award_data['gift_description'] ?? null,
            'gift_monetary_value' => $award_data['gift_monetary_value'] ?? 0,
            'catalog_item_id' => $award_data['catalog_item_id'] ?? null,
            'award_notes' => $award_data['award_notes'] ?? null,
            'is_awarded' => true,
            'awarded_by' => $user_id,
            'awarded_date' => now()
        ]);

        // Handle catalog item stock deduction
        if ($this->award_type === 'catalog' && $this->catalogItem) {
            if ($this->catalogItem->deductStock()) {
                $this->update(['stock_deducted' => true]);
            }
        }

        return $this;
    }

    /**
     * Generate certificate for winner
     */
    public function generateCertificate()
    {
        // This would generate a PDF certificate
        // Implementation depends on your PDF generation library
        $certificate_name = 'certificate_' . $this->id . '_' . time() . '.pdf';
        $certificate_path = 'certificates/' . $certificate_name;
        
        // TODO: Implement PDF generation logic here
        
        $this->update(['certificate_path' => $certificate_path]);
        
        return $certificate_path;
    }

    /**
     * Send notification to customer
     */
    public function sendNotification()
    {
        // This would send email/SMS notification to customer
        // Implementation depends on your notification system
        
        $this->update(['notification_sent' => true]);
        
        return true;
    }

    /**
     * Scope for awarded customers
     */
    public function scopeAwarded($query)
    {
        return $query->where('is_awarded', true);
    }

    /**
     * Scope for not awarded customers
     */
    public function scopeNotAwarded($query)
    {
        return $query->where('is_awarded', false);
    }

    /**
     * Scope for top winners
     */
    public function scopeTopWinners($query, $count = 3)
    {
        return $query->where('rank_position', '<=', $count);
    }

    /**
     * Scope for specific period type
     */
    public function scopePeriodType($query, $type)
    {
        return $query->where('period_type', $type);
    }

    /**
     * Get customer's award history
     */
    public static function getCustomerHistory($business_id, $customer_id, $limit = 10)
    {
        return static::where('business_id', $business_id)
            ->where('customer_id', $customer_id)
            ->orderBy('period_start', 'desc')
            ->with(['period', 'catalogItem', 'awardedBy'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get awards statistics for a business
     */
    public static function getBusinessStatistics($business_id, $period_type = null)
    {
        $query = static::where('business_id', $business_id);
        
        if ($period_type) {
            $query->where('period_type', $period_type);
        }
        
        return [
            'total_awards' => $query->count(),
            'total_awarded' => $query->where('is_awarded', true)->count(),
            'total_value_awarded' => $query->where('is_awarded', true)->sum('gift_monetary_value'),
            'unique_winners' => $query->distinct('customer_id')->count(),
            'avg_final_score' => $query->avg('final_score'),
            'top_customer' => $query->orderBy('final_score', 'desc')->with('customer')->first()
        ];
    }

    /**
     * Get monthly award trends
     */
    public static function getMonthlyTrends($business_id, $months = 12)
    {
        return static::where('business_id', $business_id)
            ->selectRaw('
                YEAR(period_start) as year,
                MONTH(period_start) as month,
                period_type,
                COUNT(*) as total_awards,
                COUNT(CASE WHEN is_awarded = 1 THEN 1 END) as awarded_count,
                SUM(gift_monetary_value) as total_value,
                AVG(final_score) as avg_score
            ')
            ->where('period_start', '>=', now()->subMonths($months))
            ->groupBy(['year', 'month', 'period_type'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
    }

    /**
     * Check if customer is repeat winner
     */
    public function getIsRepeatWinnerAttribute()
    {
        return static::where('business_id', $this->business_id)
            ->where('customer_id', $this->customer_id)
            ->where('period_type', $this->period_type)
            ->where('rank_position', '<=', 3)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    /**
     * Get customer's best rank in this period type
     */
    public function getCustomerBestRank()
    {
        return static::where('business_id', $this->business_id)
            ->where('customer_id', $this->customer_id)
            ->where('period_type', $this->period_type)
            ->min('rank_position');
    }
}