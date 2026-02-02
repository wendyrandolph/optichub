<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_template_id',
        'type',
        'title',
        'description',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProposalTemplate::class, 'proposal_template_id');
    }
}
