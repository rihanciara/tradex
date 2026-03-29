<?php

namespace Modules\AdvancedReports\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\Product;

class AwardCatalog extends Model
{
    protected $table = 'award_catalog';

    protected $fillable = [
        'business_id', 'product_id', 'award_name', 'description',
        'point_threshold', 'monetary_value', 'stock_required',
        'stock_quantity', 'award_image', 'is_active'
    ];

    protected $casts = [
        'stock_required' => 'boolean',
        'is_active' => 'boolean',
        'monetary_value' => 'decimal:4',
        'point_threshold' => 'integer',
        'stock_quantity' => 'integer'
    ];

    /**
     * Relationship to Business
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relationship to Product (optional)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship to CustomerAwards
     */
    public function awards()
    {
        return $this->hasMany(CustomerAward::class, 'catalog_item_id');
    }

    /**
     * Get active awards for a business
     */
    public static function getActiveForBusiness($business_id)
    {
        return static::where('business_id', $business_id)
            ->where('is_active', true)
            ->orderBy('award_name')
            ->get();
    }

    /**
     * Check if award can be given
     */
    public function canBeAwarded()
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->stock_required && $this->stock_quantity <= 0) {
            return false;
        }
        
        return true;
    }

    /**
     * Deduct stock when award is given
     */
    public function deductStock($quantity = 1)
    {
        if ($this->stock_required && $this->stock_quantity >= $quantity) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }
        
        return false;
    }

    /**
     * Get times this award has been given
     */
    public function getTimesAwardedAttribute()
    {
        return $this->awards()->where('is_awarded', true)->count();
    }

    /**
     * Get total value of awards given
     */
    public function getTotalValueAwardedAttribute()
    {
        return $this->awards()
            ->where('is_awarded', true)
            ->sum('gift_monetary_value');
    }

    /**
     * Get stock status for display
     */
    public function getStockStatusAttribute()
    {
        if (!$this->stock_required) {
            return 'No Stock Required';
        }
        
        if ($this->stock_quantity <= 0) {
            return 'Out of Stock';
        }
        
        if ($this->stock_quantity <= 5) {
            return 'Low Stock (' . $this->stock_quantity . ')';
        }
        
        return 'In Stock (' . $this->stock_quantity . ')';
    }

    /**
     * Get stock status color
     */
    public function getStockColorAttribute()
    {
        if (!$this->stock_required) {
            return 'default';
        }
        
        if ($this->stock_quantity <= 0) {
            return 'danger';
        }
        
        if ($this->stock_quantity <= 5) {
            return 'warning';
        }
        
        return 'success';
    }

    /**
     * Scope for available awards
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->where('stock_required', false)
                  ->orWhere('stock_quantity', '>', 0);
            });
    }

    /**
     * Scope for awards needing restock
     */
    public function scopeNeedingRestock($query, $threshold = 5)
    {
        return $query->where('is_active', true)
            ->where('stock_required', true)
            ->where('stock_quantity', '<=', $threshold);
    }

    /**
     * Get image URL
     */
    public function getImageUrlAttribute()
    {
        if ($this->award_image) {
            return asset('storage/' . $this->award_image);
        }
        
        return asset('img/default-award.png'); // Default image
    }

    /**
     * Delete with image cleanup
     */
    public function deleteWithImage()
    {
        // Delete image file if exists
        if ($this->award_image) {
            $imagePath = storage_path('app/public/' . $this->award_image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        return $this->delete();
    }
}