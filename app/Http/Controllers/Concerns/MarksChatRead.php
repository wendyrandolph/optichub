<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use App\Models\ChatRead;

trait MarksChatRead
{
    protected function markRead(int $channelId): void
    {
        $user = auth('admin')->user() ?? auth('web')->user();
        if (!$user) {
            return;
        }

        ChatRead::updateOrCreate(
            ['channel_id' => $channelId, 'user_id' => $user->id],
            ['last_read_at' => now()]
        );
    }
}
