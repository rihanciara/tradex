<?php

namespace Modules\Exchange\Entities;

use App\TransactionSellLine;
use Illuminate\Database\Eloquent\Model;

class TransactionExchangeLine extends Model
{
    // DISABLE AUTOMATIC TIMESTAMPS - this is likely causing the Carbon error
    public $timestamps = false;

    protected $fillable = [
        'exchange_id',
        'original_sell_line_id',
        'new_sell_line_id',
        'exchange_type',
        'original_quantity',
        'original_unit_price',
        'new_quantity',
        'new_unit_price',
        'price_difference',
        'created_at',  // Add these to fillable since we're handling manually
        'updated_at'
    ];

    protected $casts = [
        'original_quantity' => 'decimal:4',
        'original_unit_price' => 'decimal:4',
        'new_quantity' => 'decimal:4',
        'new_unit_price' => 'decimal:4',
        'price_difference' => 'decimal:4'
        // DO NOT cast created_at/updated_at - let them be strings
    ];

    public function exchange()
    {
        return $this->belongsTo(TransactionExchange::class, 'exchange_id');
    }

    public function originalSellLine()
    {
        return $this->belongsTo(TransactionSellLine::class, 'original_sell_line_id');
    }

    public function newSellLine()
    {
        return $this->belongsTo(TransactionSellLine::class, 'new_sell_line_id');
    }
}
