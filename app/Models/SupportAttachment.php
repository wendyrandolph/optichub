<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAttachment extends Model
{
    use HasFactory;

    protected $table = 'support_ticket_attachments';

    protected $fillable = [
        'support_ticket_id',
        'support_ticket_message_id',
        'file_path',
        'mime',
        'size',
        'original_name',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_ticket_message_id');
    }
}
