<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'created_by',
        'note_type',
        'body',
        'pinned',
        'happened_at',
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'happened_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Client::class, 'contact_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
