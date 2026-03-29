<?php

namespace Modules\Exchange\Entities;

use App\User;
use App\Business;
use App\Transaction;
use App\BusinessLocation;
use Illuminate\Database\Eloquent\Model;

class TransactionExchange extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'original_transaction_id',
        'exchange_transaction_id',
        'exchange_ref_no',
        'exchange_date',
        'original_amount',           // NEW: Amount of returned items
        'new_amount',               // NEW: Amount of new items  
        'exchange_difference',      // NEW: Difference (new - original)
        'payment_received',         // NEW: Additional payment from customer
        'refund_given',            // NEW: Refund given to customer
        'total_exchange_amount',    // Keep for backward compatibility
        'status',
        'created_by',
        'notes',
        'cancelled_by'
    ];

    protected $dates = [
        'exchange_date',
        'created_at',
        'updated_at',
        'cancelled_at'
    ];

    protected $casts = [
        'exchange_date' => 'datetime',
        'original_amount' => 'decimal:4',
        'new_amount' => 'decimal:4',
        'exchange_difference' => 'decimal:4',
        'payment_received' => 'decimal:4',
        'refund_given' => 'decimal:4',
        'total_exchange_amount' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function originalTransaction()
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    public function exchangeTransaction()
    {
        return $this->belongsTo(Transaction::class, 'exchange_transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exchangeLines()
    {
        return $this->hasMany(TransactionExchangeLine::class, 'exchange_id');
    }

    // Generate exchange reference number
    public static function generateExchangeRefNo($business_id)
    {
        $ref_count = self::where('business_id', $business_id)->count() + 1;
        return 'EXC-' . str_pad($ref_count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get exchange type based on amounts
     */
    public function getExchangeTypeAttribute()
    {
        if ($this->new_amount == 0) {
            return 'return_only';
        } elseif ($this->original_amount == 0) {
            return 'new_sale_only';
        } else {
            return 'exchange';
        }
    }

    /**
     * Get financial impact
     */
    public function getFinancialImpactAttribute()
    {
        if ($this->exchange_difference > 0) {
            return 'customer_pays';
        } elseif ($this->exchange_difference < 0) {
            return 'customer_refund';
        } else {
            return 'even_exchange';
        }
    }

    /**
     * Get formatted exchange summary
     */
    public function getExchangeSummaryAttribute()
    {
        return [
            'original_amount' => $this->original_amount,
            'new_amount' => $this->new_amount,
            'difference' => $this->exchange_difference,
            'payment_received' => $this->payment_received,
            'refund_given' => $this->refund_given,
            'net_cash_flow' => $this->payment_received - $this->refund_given,
            'exchange_type' => $this->exchange_type,
            'financial_impact' => $this->financial_impact
        ];
    }


    /**
     * Get users dropdown for exchange filters
     */
    public static function getUsersForDropdown($business_id)
    {
        $users = \App\User::where('business_id', $business_id)
            ->where('user_type', 'user')
            ->select('id', 'surname', 'first_name', 'last_name', 'username', 'email')
            ->get();

        $dropdown = ['' => __('exchange::lang.all')]; // Add "All" option

        foreach ($users as $user) {
            // Build full name with proper handling of nulls
            $name_parts = array_filter([
                $user->surname,
                $user->first_name,
                $user->last_name
            ], function ($part) {
                return !is_null($part) && trim($part) !== '';
            });

            if (!empty($name_parts)) {
                $full_name = implode(' ', $name_parts);
            } elseif (!empty($user->username)) {
                $full_name = $user->username;
            } elseif (!empty($user->email)) {
                $full_name = $user->email;
            } else {
                $full_name = 'User #' . $user->id;
            }

            $dropdown[$user->id] = $full_name;
        }

        return $dropdown;
    }
}
