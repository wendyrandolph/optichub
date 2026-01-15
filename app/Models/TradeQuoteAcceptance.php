<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeQuoteAcceptance extends Model
{
    protected $fillable = [
        'trade_quote_id',
        'signer_name',
        'signer_email',
        'signature',
        'ip',
        'user_agent',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function quote()
    {
        return $this->belongsTo(TradeQuote::class, 'trade_quote_id');
    }
}
