<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'uploaded_by',
        'category',
        'description',
        'original_name',
        'stored_path',
        'disk',
        'path',
        'mime',
        'size',
    ];

    public function contact()
    {
        return $this->belongsTo(Client::class, 'contact_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
