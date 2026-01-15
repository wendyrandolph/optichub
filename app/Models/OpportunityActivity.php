<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpportunityActivity extends Model
{
    protected $fillable = [
        'tenant_id',
        'opportunity_id',
        'user_id',
        'type',
        'body',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
