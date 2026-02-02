<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTemplatePhase extends Model
{
    protected $table = 'project_template_phases';

    protected $fillable = [
        'tenant_id',
        'project_template_id',
        'name',
        'sort_order',
        'description',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class, 'project_template_phase_id')
            ->orderBy('sort_order');
    }
}
