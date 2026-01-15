<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type', // tenant|client
        'sender_id',
        'body',
    ];

    public function conversation()
    {
        return $this->belongsTo(ProjectConversation::class);
    }
}
