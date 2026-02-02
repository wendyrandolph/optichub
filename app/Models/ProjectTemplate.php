<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ProjectTemplate extends Model
{
    protected $table = 'project_templates';

    protected $fillable = [
        'tenant_id',
        'workspace_type',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectTemplatePhase::class)
            ->orderBy('sort_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class)
            ->orderBy('sort_order');
    }

    public function scopeForTenantOrGlobal(Builder $query, int $tenantId): Builder
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
        });
    }

    public function scopeForWorkspaceType(Builder $query, ?string $workspaceType): Builder
    {
        if (!$workspaceType) {
            return $query;
        }

        return $query->where(function ($q) use ($workspaceType) {
            $q->whereNull('workspace_type')->orWhere('workspace_type', $workspaceType);
        });
    }
}
