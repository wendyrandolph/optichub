<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplateTask extends Model
{
    protected $table = 'project_template_tasks';

    protected $fillable = [
        'tenant_id',
        'project_template_id',
        'project_template_phase_id',
        'title',
        'description',
        'sort_order',
        'due_offset_days',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplatePhase::class, 'project_template_phase_id');
    }
}
