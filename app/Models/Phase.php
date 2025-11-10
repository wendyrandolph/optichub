<?php // app/Models/Phase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
  protected $table = 'project_phases'; // ← matches the migration
  protected $fillable = ['tenant_id', 'project_id', 'name', 'code', 'sort_order', 'description'];

  public function project()
  {
    return $this->belongsTo(Project::class);
  }
}
