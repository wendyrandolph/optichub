<?php

namespace App\Models;

// app/Models/ProjectConversation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectConversation extends Model
{
    protected $fillable = [
        'tenant_id',
        'project_id',
        'company_name',
        'approval_status',
        'approval_note',
        'approved_at',
        'approved_by_contact_id',
        'public_token',
        'public_expires_at',
        'last_message_at',
        'public_last_viewed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'public_expires_at' => 'datetime',
        'last_message_at' => 'datetime',
        'public_last_viewed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function messages()
    {
        return $this->hasMany(ProjectMessage::class, 'conversation_id');
    }

    public function approver()
    {
        return $this->belongsTo(Client::class, 'approved_by_contact_id');
    }
}
