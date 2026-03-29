<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\Contact;
use App\User;

class CustomerEngagement extends Model
{
    protected $table = 'customer_engagements';

    protected $fillable = [
        'business_id', 'customer_id', 'engagement_type', 'points',
        'verification_notes', 'platform', 'reference_url',
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
     * Relationship to Customer (Contact)
     */
    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    /**
     * Relationship to User who recorded
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get engagement types with labels
     */
    public static function getEngagementTypes()
    {
        return [
            'youtube_follow' => __('YouTube Follow/Subscribe'),
            'facebook_follow' => __('Facebook Follow/Like'),
            'instagram_follow' => __('Instagram Follow'),
            'twitter_follow' => __('Twitter Follow'),
            'content_share' => __('Content Share'),
            'review' => __('Review/Testimonial'),
            'google_review' => __('Google Review'),
            'referral' => __('Customer Referral'),
            'other' => __('Other')
        ];
    }

    /**
     * Get engagement type name for display
     */
    public function getEngagementTypeNameAttribute()
    {
        $types = self::getEngagementTypes();
        return $types[$this->engagement_type] ?? ucfirst(str_replace('_', ' ', $this->engagement_type));
    }

    /**
     * Get platform icon
     */
    public function getPlatformIconAttribute()
    {
        $icons = [
            'youtube' => 'fa-youtube',
            'facebook' => 'fa-facebook',
            'instagram' => 'fa-instagram', 
            'twitter' => 'fa-twitter',
            'google' => 'fa-google',
            'whatsapp' => 'fa-whatsapp',
            'linkedin' => 'fa-linkedin',
            'tiktok' => 'fa-tiktok'
        ];
        
        $platform = strtolower($this->platform);
        return $icons[$platform] ?? 'fa-share-alt';
    }

    /**
     * Get platform color
     */
    public function getPlatformColorAttribute()
    {
        $colors = [
            'youtube' => '#FF0000',
            'facebook' => '#1877F2',
            'instagram' => '#E4405F',
            'twitter' => '#1DA1F2',
            'google' => '#4285F4',
            'whatsapp' => '#25D366',
            'linkedin' => '#0077B5',
            'tiktok' => '#000000'
        ];
        
        $platform = strtolower($this->platform);
        return $colors[$platform] ?? '#000000';
    }
}