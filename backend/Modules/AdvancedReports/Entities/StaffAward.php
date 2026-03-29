<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\User;
use App\Variation;

class StaffAward extends Model
{
    protected $table = 'staff_awards';

    protected $fillable = [
        'business_id', 'staff_id', 'period_id', 'period_type',
        'period_start', 'period_end', 'rank_position', 'sales_total',
        'transaction_count', 'avg_transaction_value', 'performance_points', 'final_score',
        'award_type', 'catalog_item_id', 'award_quantity', 'gift_description', 'gift_monetary_value',
        'stock_deducted', 'award_notes', 'is_awarded', 'awarded_by', 'awarded_date',
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
        'performance_points' => 'integer',
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
     * Relationship to Staff (User)
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Relationship to StaffAwardPeriod
     */
    public function period()
    {
        return $this->belongsTo(StaffAwardPeriod::class, 'period_id');
    }

    /**
     * Relationship to Product Variation (for catalog awards)
     */
    public function productVariation()
    {
        return $this->belongsTo(Variation::class, 'catalog_item_id');
    }

    /**
     * Relationship to User who awarded
     */
    public function awardedBy()
    {
        return $this->belongsTo(User::class, 'awarded_by');
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
     * Get staff display name
     */
    public function getStaffDisplayNameAttribute()
    {
        if (!$this->staff) {
            return 'Unknown Staff';
        }
        
        return trim($this->staff->first_name . ' ' . $this->staff->surname);
    }

    /**
     * Get period display
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
     * Award the staff member
     */
    public function awardStaff($award_data, $user_id)
    {
        $this->update([
            'award_type' => $award_data['award_type'],
            'gift_description' => $award_data['gift_description'] ?? null,
            'gift_monetary_value' => $award_data['gift_monetary_value'] ?? 0,
            'catalog_item_id' => $award_data['catalog_item_id'] ?? null,
            'award_quantity' => $award_data['award_quantity'] ?? 1,
            'award_notes' => $award_data['award_notes'] ?? null,
            'is_awarded' => true,
            'awarded_by' => $user_id,
            'awarded_date' => now()
        ]);

        // Handle catalog item stock deduction
        if ($this->award_type === 'catalog' && $this->productVariation) {
            // Implementation would integrate with stock management
        }

        return $this;
    }

    /**
     * Scope for awarded staff
     */
    public function scopeAwarded($query)
    {
        return $query->where('is_awarded', true);
    }

    /**
     * Scope for not awarded staff
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
}