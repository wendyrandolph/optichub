<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ChatChannel extends Model
{
    use HasFactory, HasTenantScope;

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'project_id',
        'trade_job_id',
        'dm_key',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')->latest();
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class, 'channel_id')->latestOfMany();
    }

    public function readStates(): HasMany
    {
        return $this->hasMany(ChatRead::class, 'channel_id');
    }
    public function messagesChronological(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')->oldest();
    }
    public function tradeJob()
    {
        return $this->belongsTo(\App\Models\TradeJob::class, 'trade_job_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_channel_users', 'channel_id', 'user_id')
            ->withTimestamps();
    }

    public static function findOrCreateDm(int $tenantId, User $userA, User $userB): self
    {
        if ((int) $userA->tenant_id !== $tenantId || (int) $userB->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('DM users must belong to the same tenant.');
        }

        if ((int) $userA->id === (int) $userB->id) {
            throw new InvalidArgumentException('DM users must be different.');
        }

        $userIds = collect([$userA->id, $userB->id])->sort()->values();
        $dmKey = 'dm:' . $userIds[0] . ':' . $userIds[1];

        $hasDmKey = Schema::hasColumn('chat_channels', 'dm_key');

        if ($hasDmKey) {
            $existing = static::query()
                ->where('tenant_id', $tenantId)
                ->where('type', 'dm')
                ->where('dm_key', $dmKey)
                ->first();
        } else {
            $existing = null;
        }

        if ($existing) {
            return $existing;
        }

        $legacy = static::query()
            ->where('tenant_id', $tenantId)
            ->where('type', 'dm')
            ->whereHas('users', fn ($q) => $q->where('users.id', $userIds[0]))
            ->whereHas('users', fn ($q) => $q->where('users.id', $userIds[1]))
            ->first();

        if ($legacy && $hasDmKey) {
            $legacy->dm_key = $dmKey;
            $legacy->save();
            return $legacy;
        }

        $displayName = function (User $user): string {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($name !== '') {
                return $name;
            }

            return $user->email ?? ('User #' . $user->id);
        };

        return DB::transaction(function () use ($tenantId, $userA, $userB, $displayName, $hasDmKey, $dmKey) {
            $payload = [
                'tenant_id' => $tenantId,
                'name' => 'DM: ' . $displayName($userA) . ' & ' . $displayName($userB),
                'type' => 'dm',
                'project_id' => null,
                'trade_job_id' => null,
            ];
            if ($hasDmKey) {
                $payload['dm_key'] = $dmKey;
            }

            $channel = static::create($payload);

            $channel->users()->sync([$userA->id, $userB->id]);

            return $channel;
        });
    }
}
