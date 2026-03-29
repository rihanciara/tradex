<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\User;

class StaffAwardPeriod extends Model
{
    protected $table = 'staff_award_periods';

    protected $fillable = [
        'business_id', 'period_type', 'period_start', 'period_end',
        'winner_count', 'is_finalized', 'finalized_at', 'finalized_by'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'is_finalized' => 'boolean',
        'finalized_at' => 'datetime',
        'winner_count' => 'integer'
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
     * Relationship to Staff Awards
     */
    public function awards()
    {
        return $this->hasMany(StaffAward::class, 'period_id');
    }

    /**
     * Get period label for display
     */
    public function getPeriodLabelAttribute()
    {
        return ucfirst($this->period_type) . ' (' . 
               $this->period_start->format('M d') . ' - ' . 
               $this->period_end->format('M d, Y') . ')';
    }
}