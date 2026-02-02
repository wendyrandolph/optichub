<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;


class TeamMember extends Model
{
    use HasFactory, HasTenantScope, \App\Traits\FormatsPhone;

    protected $table = 'team_members';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'firstName',      // camelCase to match your columns
        'lastName',
        'email',
        'phone',
        'role',
        'title',
        'status',
        'avatar',
        'color_hex',
        'notes',
        'can_manage_support',
        'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'can_manage_support' => 'boolean',
    ];

    // Append computed attributes when toArray()/JSON
    protected $appends = ['fullName'];

    // --- Relationships ---
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Only keep this if you actually have a user_id column pointing to App\Models\User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // assumes team_members.user_id exists
    }

    // --- Query Scopes ---
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getPhoneFormattedAttribute(): ?string
    {
        return $this->formatPhone($this->attributes['phone'] ?? null);
    }

    // --- Accessors / Mutators ---

    /**
     * Backwards-compatible accessor so calling $member->fullName works.
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    // Hash password automatically if set/changed
    protected function password(): Attribute
    {
        return Attribute::set(function ($value) {
            if (blank($value)) {
                return null;
            }
            // Avoid double-hashing (very basic check)
            return \Illuminate\Support\Str::startsWith($value, '$2y$') ? $value : bcrypt($value);
        });
    }
}
