<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class Task extends Model
{
  protected $fillable = [
    'tenant_id',
    'user_id',
    'project_id',
    'client_id',
    'phase_id',
    'title',
    'description',
    'due_date',
    'status',    // e.g. 'todo','in_progress','completed','archived' (up to you)
    'priority',  // e.g. 'low','medium','high'
  ];

  protected $casts = [
    'due_date' => 'date',
  ];

  public function tenant(): BelongsTo
  {
    return $this->belongsTo(Tenant::class);
  }
  // app/Models/Task.php
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class)->withDefault([
      'display_name' => 'Unassigned',
    ]);
  }

  public function project(): BelongsTo
  {
    return $this->belongsTo(Project::class);
  }
  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class);
  }
  public function comments(): HasMany
  {
    return $this->hasMany(TaskComment::class);
  }

  public function phase(): BelongsTo
  {
    return $this->belongsTo(\App\Models\Phase::class, 'phase_id'); // or your Phase model/table name
  }
  // ---- Scopes
  public function scopeForTenant($query, int $tenantId)
  {
    return $query->where('tenant_id', $tenantId);
  }

  // Match legacy "assigned()" usage; defaults to current user if no param given
  public function scopeAssigned($q, ?int $userId = null)
  {
    $userId = $userId ?? Auth::id();
    return $q->where('user_id', $userId);
  }

  // Unassigned tasks
  public function scopeUnassigned($q)
  {
    return $q->whereNull('user_id');
  }



  public function scopeForUser($query, int $userId)
  {
    return $query->where('user_id', $userId);
  }

  public function scopeOpen($query)
  {
    // adjust if you use different “open” statuses
    return $query->whereIn('status', ['todo', 'in_progress']);
  }

  public function scopeCompleted($query)
  {
    return $query->where('status', 'completed');
  }

  public function scopeOverdue($query)
  {
    return $query->whereDate('due_date', '<', now())->where('status', '!=', 'completed');
  }

  public function scopeDueToday($query)
  {
    return $query->whereDate('due_date', now()->toDateString());
  }


  public static function countCompletedBetween($startDate, $endDate)
  {
    return self::where('status', 'completed')
      ->whereBetween('updated_at', [$startDate, $endDate])
      ->count();
  }
  public static function countUnassigned()
  {
    return self::whereNull('user_id')->count();
  }

  public static function countStuck()
  {
    // Define "stuck" as tasks that are open and overdue
    return self::open()->overdue()->count();
  }



  public static function openByAssigneeWithNames(?int $tenantId = null): Collection
  {
    $tasks = self::open()
      ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
      ->with(['user:id,first_name,last_name,username']) // <- snake_case
      ->get();

    return $tasks
      ->groupBy(fn($t) => optional($t->user)->display_name ?? 'Unassigned')
      ->sortKeys();
  }



  public static function getOverdueTasks()
  {
    return self::overdue()->get();
  }

  public static function getTasksDueToday()
  {
    return self::dueToday()->get();
  }

  public static function onTimeCompletionBetween($startDate, $endDate)
  {
    return self::where('status', 'completed')
      ->whereColumn('updated_at', '<=', 'due_date')
      ->whereBetween('updated_at', [$startDate, $endDate])
      ->count();
  }

  public static function countOverdueOpen()
  {
    return self::overdue()->count();
  }

  public static function getCompletedTaskCountsByUser()
  {
    return self::where('status', 'completed')
      ->selectRaw('user_id, COUNT(*) as completed_count')
      ->groupBy('user_id')
      ->with('user:id,first_name,last_name,username')
      ->get();
  }
  public function assigneeName(): string
  {
    // Optional helper for views
    if ($this->assign_type === 'admin') {
      return optional(\App\Models\TeamMember::find($this->assign_id))->firstName
        ?? optional($this->user)->display_name
        ?? 'Unassigned';
    }
    if ($this->assign_type === 'client') {
      return optional(\App\Models\Client::find($this->assign_id))->client_name ?? 'Client';
    }
    return 'Unassigned';
  }
}
