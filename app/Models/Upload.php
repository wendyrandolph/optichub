<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;

class Upload extends Model
{
  protected $fillable = [
    'tenant_id',
    'contact_id',
    'project_id',
    'task_id',
    'original_name',
    'stored_name',
    'mime_type',
    'size',
    'path',
    'client_visible',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }

  public function client()
  {
    return $this->belongsTo(Client::class, 'contact_id');
  }

  public function project()
  {
    return $this->belongsTo(Project::class);
  }

  public function task()
  {
    return $this->belongsTo(Task::class);
  }
}
