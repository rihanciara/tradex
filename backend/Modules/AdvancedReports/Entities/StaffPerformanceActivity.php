<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\User;

class StaffPerformanceActivity extends Model
{
    protected $table = 'staff_performance_activities';

    protected $fillable = [
        'business_id', 'staff_id', 'activity_type', 'points',
        'description', 'reference_url', 'verification_notes',
        'recorded_by', 'recorded_date', 'status'
    ];

    protected $casts = [
        'recorded_date' => 'date',
        'points' => 'integer'
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
     * Relationship to User who recorded
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get activity types with labels
     */
    public static function getActivityTypes()
    {
        return [
            'punctuality' => __('Punctuality'),
            'customer_service' => __('Customer Service Excellence'),
            'upselling' => __('Upselling Success'),
            'teamwork' => __('Teamwork'),
            'training_completion' => __('Training Completion'),
            'cleanliness' => __('Cleanliness & Organization'),
            'other' => __('Other')
        ];
    }

    /**
     * Get activity type name for display
     */
    public function getActivityTypeNameAttribute()
    {
        $types = self::getActivityTypes();
        return $types[$this->activity_type] ?? ucfirst(str_replace('_', ' ', $this->activity_type));
    }

    /**
     * Get activity icon
     */
    public function getActivityIconAttribute()
    {
        $icons = [
            'punctuality' => 'fa-clock-o',
            'customer_service' => 'fa-smile-o',
            'upselling' => 'fa-arrow-up',
            'teamwork' => 'fa-users',
            'training_completion' => 'fa-graduation-cap',
            'cleanliness' => 'fa-check-circle',
            'other' => 'fa-star'
        ];
        
        return $icons[$this->activity_type] ?? 'fa-star';
    }

    /**
     * Get activity color
     */
    public function getActivityColorAttribute()
    {
        $colors = [
            'punctuality' => '#28a745',
            'customer_service' => '#007bff',
            'upselling' => '#ffc107',
            'teamwork' => '#6f42c1',
            'training_completion' => '#fd7e14',
            'cleanliness' => '#20c997',
            'other' => '#6c757d'
        ];
        
        return $colors[$this->activity_type] ?? '#007bff';
    }
}