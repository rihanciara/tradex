<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;

class ReportConfiguration extends Model
{
    protected $table = 'advanced_report_configurations';

    protected $fillable = [
        'report_type',
        'report_name',
        'columns',
        'filters',
        'settings',
        'is_active',
        'created_by'
    ];

    /**
     * IMPORTANT: Cast JSON columns to arrays
     */
    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Safe method to get columns
     */
    public function getColumnsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }

    /**
     * Safe method to get filters  
     */
    public function getFiltersAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }

    /**
     * Safe method to get settings
     */
    public function getSettingsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }
}

class ReportSchedule extends Model
{
    protected $table = 'advanced_report_schedules';

    protected $fillable = [
        'business_id',
        'report_type',
        'name',
        'filters',
        'frequency',
        'email_recipients',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by'
    ];

    protected $casts = [
        'filters' => 'array',
        'email_recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime'
    ];

    /**
     * Safe method to get filters
     */
    public function getFiltersAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }

    /**
     * Safe method to get email recipients
     */
    public function getEmailRecipientsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }
}

class ReportExport extends Model
{
    protected $table = 'advanced_report_exports';

    protected $fillable = [
        'business_id',
        'report_type',
        'file_name',
        'file_path',
        'export_format',
        'filters',
        'total_records',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'created_by'
    ];

    protected $casts = [
        'filters' => 'array',
        'total_records' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Safe method to get filters
     */
    public function getFiltersAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }
}

class SavedFilter extends Model
{
    protected $table = 'advanced_report_saved_filters';

    protected $fillable = [
        'business_id',
        'user_id',
        'report_type',
        'filter_name',
        'filter_data',
        'is_default'
    ];

    protected $casts = [
        'filter_data' => 'array',
        'is_default' => 'boolean'
    ];

    /**
     * Safe method to get filter data
     */
    public function getFilterDataAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }
}
