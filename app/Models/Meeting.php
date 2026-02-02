<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'location',
        'project_id',
        'contact_id',
        'member_id',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
