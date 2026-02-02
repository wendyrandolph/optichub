<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWorkPreference extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'weekly_target_hours',
        'working_days_json',
    ];

    protected $casts = [
        'weekly_target_hours' => 'integer',
        'working_days_json' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
