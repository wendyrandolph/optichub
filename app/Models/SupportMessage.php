<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasFactory;

    protected $table = 'support_ticket_messages';

    protected $fillable = [
        'support_ticket_id',
        'sender_type',
        'sender_user_id',
        'sender_admin_id',
        'user_id',
        'body',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function adminAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_admin_id');
    }
}
