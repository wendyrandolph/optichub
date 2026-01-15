<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeQuoteItem extends Model
{
    protected $fillable = [
        'trade_quote_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(TradeQuote::class, 'trade_quote_id');
    }
}
