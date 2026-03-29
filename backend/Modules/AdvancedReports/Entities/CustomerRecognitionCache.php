<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerRecognitionCache extends Model
{
    protected $table = 'customer_recognition_cache';

    protected $fillable = [
        'business_id', 
        'customer_id', 
        'period_type', 
        'period_start',
        'period_end', 
        'sales_total', 
        'engagement_points', 
        'final_score',
        'transaction_count', 
        'current_rank', 
        'last_updated'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'sales_total' => 'decimal:4',
        'final_score' => 'decimal:4',
        'last_updated' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    /**
     * Scopes
     */
    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function scopeForPeriod($query, $period_type, $period_start, $period_end)
    {
        return $query->where('period_type', $period_type)
                    ->where('period_start', $period_start)
                    ->where('period_end', $period_end);
    }

    public function scopeByRank($query)
    {
        return $query->orderBy('final_score', 'desc')
                    ->orderBy('sales_total', 'desc')
                    ->orderBy('engagement_points', 'desc');
    }

    public function scopeTopRanked($query, $limit = 10)
    {
        return $query->byRank()->limit($limit);
    }

    /**
     * Get cached data for a specific customer and period
     */
    public static function getCustomerCache($business_id, $customer_id, $period_type, $period_start, $period_end)
    {
        return static::forBusiness($business_id)
            ->where('customer_id', $customer_id)
            ->forPeriod($period_type, $period_start, $period_end)
            ->first();
    }

    /**
     * Update or create cache entry for a customer
     */
    public static function updateCustomerCache($business_id, $customer_id, $period_type, $period_start, $period_end, $data)
    {
        return static::updateOrCreate([
            'business_id' => $business_id,
            'customer_id' => $customer_id,
            'period_type' => $period_type,
            'period_start' => $period_start,
            'period_end' => $period_end
        ], array_merge($data, [
            'last_updated' => now()
        ]));
    }

    /**
     * Get rankings for a period with pagination
     */
    public static function getRankingsForPeriod($business_id, $period_type, $period_start, $period_end, $limit = null, $offset = 0)
    {
        $query = static::with(['customer'])
            ->forBusiness($business_id)
            ->forPeriod($period_type, $period_start, $period_end)
            ->byRank();

        if ($limit) {
            $query->limit($limit)->offset($offset);
        }

        return $query->get();
    }

    /**
     * Get current period rankings
     */
    public static function getCurrentPeriodRankings($business_id, $period_type, $limit = 10)
    {
        $dates = self::getCurrentPeriodDates($period_type);
        
        return static::getRankingsForPeriod(
            $business_id, 
            $period_type, 
            $dates['start'], 
            $dates['end'], 
            $limit
        );
    }

    /**
     * Get top performer for a period
     */
    public static function getTopPerformer($business_id, $period_type, $period_start, $period_end)
    {
        return static::with(['customer'])
            ->forBusiness($business_id)
            ->forPeriod($period_type, $period_start, $period_end)
            ->byRank()
            ->first();
    }

    /**
     * Get current period winners (top performer for each period type)
     */
    public static function getCurrentWinners($business_id)
    {
        $winners = [];
        $periods = ['weekly', 'monthly', 'yearly'];

        foreach ($periods as $period_type) {
            $dates = self::getCurrentPeriodDates($period_type);
            $winners[$period_type] = static::getTopPerformer(
                $business_id, 
                $period_type, 
                $dates['start'], 
                $dates['end']
            );
        }

        return $winners;
    }

    /**
     * Update rankings (current_rank) for a period
     */
    public static function updateRankingsForPeriod($business_id, $period_type, $period_start, $period_end)
    {
        $rankings = static::forBusiness($business_id)
            ->forPeriod($period_type, $period_start, $period_end)
            ->byRank()
            ->get();

        foreach ($rankings as $index => $ranking) {
            $ranking->update(['current_rank' => $index + 1]);
        }

        return $rankings->count();
    }

    /**
     * Get statistics for a period
     */
    public static function getPeriodStatistics($business_id, $period_type, $period_start, $period_end)
    {
        return static::forBusiness($business_id)
            ->forPeriod($period_type, $period_start, $period_end)
            ->selectRaw('
                COUNT(*) as total_participants,
                SUM(sales_total) as total_sales,
                SUM(engagement_points) as total_engagement_points,
                AVG(final_score) as avg_score,
                MAX(final_score) as top_score,
                SUM(transaction_count) as total_transactions,
                AVG(sales_total / NULLIF(transaction_count, 0)) as avg_transaction_value
            ')
            ->first();
    }

    /**
     * Clear cache for a specific period
     */
    public static function clearPeriodCache($business_id, $period_type, $period_start, $period_end)
    {
        return static::forBusiness($business_id)
            ->forPeriod($period_type, $period_start, $period_end)
            ->delete();
    }

    /**
     * Clear all cache for a business
     */
    public static function clearBusinessCache($business_id)
    {
        return static::forBusiness($business_id)->delete();
    }

    /**
     * Check if cache is stale (older than specified minutes)
     */
    public function isStale($minutes = 60)
    {
        return $this->last_updated->addMinutes($minutes)->isPast();
    }

    /**
     * Get cache age in minutes
     */
    public function getCacheAgeAttribute()
    {
        return $this->last_updated->diffInMinutes(now());
    }

    /**
     * Get rank suffix (1st, 2nd, 3rd, etc.)
     */
    public function getRankSuffixAttribute()
    {
        $rank = $this->current_rank;
        
        if (!$rank) return '';
        
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
     * Get performance trend (comparing with previous period)
     */
    public function getPerformanceTrend()
    {
        $previousDates = self::getPreviousPeriodDates($this->period_type, $this->period_start);
        
        $previousCache = static::forBusiness($this->business_id)
            ->where('customer_id', $this->customer_id)
            ->forPeriod($this->period_type, $previousDates['start'], $previousDates['end'])
            ->first();

        if (!$previousCache) {
            return [
                'trend' => 'new',
                'rank_change' => null,
                'score_change' => null,
                'sales_change' => null
            ];
        }

        $rank_change = $previousCache->current_rank - $this->current_rank; // Positive = improvement
        $score_change = $this->final_score - $previousCache->final_score;
        $sales_change = $this->sales_total - $previousCache->sales_total;

        return [
            'trend' => $rank_change > 0 ? 'up' : ($rank_change < 0 ? 'down' : 'same'),
            'rank_change' => $rank_change,
            'score_change' => $score_change,
            'sales_change' => $sales_change,
            'previous_rank' => $previousCache->current_rank,
            'previous_score' => $previousCache->final_score,
            'previous_sales' => $previousCache->sales_total
        ];
    }

    /**
     * Bulk update cache for multiple customers
     */
    public static function bulkUpdateCache($business_id, $period_type, $period_start, $period_end, $customer_data)
    {
        $updated = 0;
        
        DB::beginTransaction();
        
        try {
            // Clear existing cache for this period
            static::clearPeriodCache($business_id, $period_type, $period_start, $period_end);
            
            // Insert new cache data
            foreach ($customer_data as $data) {
                static::create(array_merge($data, [
                    'business_id' => $business_id,
                    'period_type' => $period_type,
                    'period_start' => $period_start,
                    'period_end' => $period_end,
                    'last_updated' => now()
                ]));
                $updated++;
            }
            
            // Update rankings
            static::updateRankingsForPeriod($business_id, $period_type, $period_start, $period_end);
            
            DB::commit();
            
            return $updated;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get expired cache entries
     */
    public static function getExpiredCache($minutes = 60)
    {
        return static::where('last_updated', '<', now()->subMinutes($minutes));
    }

    /**
     * Clean up old cache entries
     */
    public static function cleanupOldCache($days = 90)
    {
        return static::where('last_updated', '<', now()->subDays($days))->delete();
    }

    /**
     * Helper: Get current period dates
     */
    private static function getCurrentPeriodDates($period_type)
    {
        $now = Carbon::now();
        
        switch ($period_type) {
            case 'weekly':
                return [
                    'start' => $now->startOfWeek()->format('Y-m-d'),
                    'end' => $now->endOfWeek()->format('Y-m-d')
                ];
            case 'monthly':
                return [
                    'start' => $now->startOfMonth()->format('Y-m-d'),
                    'end' => $now->endOfMonth()->format('Y-m-d')
                ];
            case 'yearly':
                return [
                    'start' => $now->startOfYear()->format('Y-m-d'),
                    'end' => $now->endOfYear()->format('Y-m-d')
                ];
            default:
                throw new \InvalidArgumentException("Invalid period type: {$period_type}");
        }
    }

    /**
     * Helper: Get previous period dates
     */
    private static function getPreviousPeriodDates($period_type, $current_start)
    {
        $date = Carbon::parse($current_start);
        
        switch ($period_type) {
            case 'weekly':
                $previous = $date->subWeek();
                return [
                    'start' => $previous->startOfWeek()->format('Y-m-d'),
                    'end' => $previous->endOfWeek()->format('Y-m-d')
                ];
            case 'monthly':
                $previous = $date->subMonth();
                return [
                    'start' => $previous->startOfMonth()->format('Y-m-d'),
                    'end' => $previous->endOfMonth()->format('Y-m-d')
                ];
            case 'yearly':
                $previous = $date->subYear();
                return [
                    'start' => $previous->startOfYear()->format('Y-m-d'),
                    'end' => $previous->endOfYear()->format('Y-m-d')
                ];
            default:
                throw new \InvalidArgumentException("Invalid period type: {$period_type}");
        }
    }

    /**
     * Get cache summary for admin/debug purposes
     */
    public static function getCacheSummary($business_id)
    {
        return static::forBusiness($business_id)
            ->selectRaw('
                period_type,
                COUNT(*) as total_cached_customers,
                MIN(last_updated) as oldest_cache,
                MAX(last_updated) as newest_cache,
                AVG(final_score) as avg_score,
                MAX(final_score) as max_score
            ')
            ->groupBy('period_type')
            ->get();
    }
}