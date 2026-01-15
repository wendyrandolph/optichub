<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactActivity extends Model
{
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'actor_id',
        'type',
        'meta',
        'happened_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'happened_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Client::class, 'contact_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
