<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\User;
use Carbon\Carbon;

class AwardPeriod extends Model
{
    protected $table = 'award_periods';

    protected $fillable = [
        'business_id', 'period_type', 'period_start', 'period_end',
        'is_finalized', 'finalized_at', 'finalized_by',
        'total_participants', 'winners_count', 'period_summary'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'is_finalized' => 'boolean',
        'finalized_at' => 'datetime',
        'period_summary' => 'array',
        'total_participants' => 'integer',
        'winners_count' => 'integer'
    ];

    /**
     * Relationship to Business
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relationship to User who finalized
     */
    public function finalizedBy()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Relationship to CustomerAwards
     */
    public function awards()
    {
        return $this->hasMany(CustomerAward::class, 'period_id');
    }

    /**
     * Get current period for business and type
     */
    public static function getCurrentPeriod($business_id, $period_type)
    {
        $dates = self::getPeriodDates($period_type);
        
        return static::firstOrCreate([
            'business_id' => $business_id,
            'period_type' => $period_type,
            'period_start' => $dates['start']
        ], [
            'period_end' => $dates['end'],
            'total_participants' => 0,
            'winners_count' => 0
        ]);
    }

/**
 * Get period dates based on type
 */
public static function getPeriodDates($period_type, $date = null)
{
    $date = $date ? Carbon::parse($date) : Carbon::now();
    
    switch ($period_type) {
        case 'weekly':
            return [
                'start' => $date->copy()->startOfWeek()->format('Y-m-d'),
                'end' => $date->copy()->endOfWeek()->format('Y-m-d')
            ];
        case 'monthly':
            return [
                'start' => $date->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $date->copy()->endOfMonth()->format('Y-m-d')
            ];
        case 'yearly':
            return [
                'start' => $date->copy()->startOfYear()->format('Y-m-d'),
                'end' => $date->copy()->endOfYear()->format('Y-m-d')
            ];
        default:
            throw new \InvalidArgumentException("Invalid period type: {$period_type}");
    }
}

    /**
     * Get previous period dates
     */
    public static function getPreviousPeriodDates($period_type, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        
        switch ($period_type) {
            case 'weekly':
                $date->subWeek();
                break;
            case 'monthly':
                $date->subMonth();
                break;
            case 'yearly':
                $date->subYear();
                break;
        }
        
        return self::getPeriodDates($period_type, $date);
    }

    /**
     * Check if period can be finalized
     */
    public function canBeFinalized()
    {
        if ($this->is_finalized) {
            return false;
        }
        
        // Can only finalize past periods or current period that has ended
        return $this->period_end <= Carbon::now()->format('Y-m-d');
    }

    /**
     * Finalize the period
     */
    public function finalize($user_id, $summary_data = [])
    {
        if (!$this->canBeFinalized()) {
            throw new \Exception('Period cannot be finalized yet');
        }
        
        $this->update([
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => $user_id,
            'period_summary' => $summary_data
        ]);
        
        return $this;
    }

    /**
     * Get period label for display
     */
    public function getPeriodLabelAttribute()
    {
        $start = Carbon::parse($this->period_start);
        $end = Carbon::parse($this->period_end);
        
        switch ($this->period_type) {
            case 'weekly':
                return 'Week of ' . $start->format('M d') . ' - ' . $end->format('M d, Y');
            case 'monthly':
                return $start->format('F Y');
            case 'yearly':
                return $start->format('Y');
            default:
                return $start->format('M d') . ' - ' . $end->format('M d, Y');
        }
    }

    /**
     * Get period status
     */
    public function getStatusAttribute()
    {
        if ($this->is_finalized) {
            return 'finalized';
        }
        
        $now = Carbon::now()->format('Y-m-d');
        
        if ($this->period_end < $now) {
            return 'ended';
        }
        
        if ($this->period_start <= $now && $this->period_end >= $now) {
            return 'active';
        }
        
        return 'future';
    }

    /**
     * Get status color for display
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'finalized':
                return 'success';
            case 'ended':
                return 'warning';
            case 'active':
                return 'primary';
            case 'future':
                return 'info';
            default:
                return 'default';
        }
    }

    /**
     * Scope for finalized periods
     */
    public function scopeFinalized($query)
    {
        return $query->where('is_finalized', true);
    }

    /**
     * Scope for active periods
     */
    public function scopeActive($query)
    {
        $now = Carbon::now()->format('Y-m-d');
        return $query->where('period_start', '<=', $now)
            ->where('period_end', '>=', $now)
            ->where('is_finalized', false);
    }

    /**
     * Scope for ended but not finalized periods
     */
    public function scopeEndedNotFinalized($query)
    {
        $now = Carbon::now()->format('Y-m-d');
        return $query->where('period_end', '<', $now)
            ->where('is_finalized', false);
    }

    /**
     * Get all periods for a business and type
     */
    public static function getPeriodsForBusiness($business_id, $period_type, $limit = 12)
    {
        return static::where('business_id', $business_id)
            ->where('period_type', $period_type)
            ->orderBy('period_start', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate period performance compared to previous
     */
    public function getPerformanceComparison()
    {
        $previous_dates = self::getPreviousPeriodDates($this->period_type, $this->period_start);
        
        $previous_period = static::where('business_id', $this->business_id)
            ->where('period_type', $this->period_type)
            ->where('period_start', $previous_dates['start'])
            ->first();
        
        if (!$previous_period || !$previous_period->period_summary) {
            return null;
        }
        
        $current_summary = $this->period_summary ?: [];
        $previous_summary = $previous_period->period_summary;
        
        return [
            'participants_change' => $this->calculatePercentageChange(
                $previous_summary['total_participants'] ?? 0,
                $current_summary['total_participants'] ?? 0
            ),
            'sales_change' => $this->calculatePercentageChange(
                $previous_summary['total_sales'] ?? 0,
                $current_summary['total_sales'] ?? 0
            ),
            'avg_score_change' => $this->calculatePercentageChange(
                $previous_summary['avg_score'] ?? 0,
                $current_summary['avg_score'] ?? 0
            )
        ];
    }

    /**
     * Calculate percentage change
     */
    private function calculatePercentageChange($old_value, $new_value)
    {
        if ($old_value == 0) {
            return $new_value > 0 ? 100 : 0;
        }
        
        return round((($new_value - $old_value) / $old_value) * 100, 2);
    }

    /**
     * Get winners count for the period
     */
    public function getWinnersAttribute()
    {
        return $this->awards()->orderBy('rank_position')->get();
    }

    /**
     * Check if period is current
     */
    public function getIsCurrentAttribute()
    {
        $now = Carbon::now()->format('Y-m-d');
        return $this->period_start <= $now && $this->period_end >= $now;
    }
}