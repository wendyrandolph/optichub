<?php

namespace Tests\Feature;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_trades_chat_marks_unread_and_sets_read_marker(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Chat',
            'workspace_type' => 'trades',
        ]);

        $viewer = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'View',
            'last_name' => 'User',
            'username' => 'viewer',
            'email' => 'viewer@test.local',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $sender = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Send',
            'last_name' => 'User',
            'username' => 'sender',
            'email' => 'sender@test.local',
            'role' => 'employee',
            'password' => Hash::make('password123'),
        ]);

        $channel = ChatChannel::create([
            'tenant_id' => $tenant->id,
            'name' => 'Team',
            'type' => 'tenant',
        ]);

        ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $sender->id,
            'body' => 'Hello team',
        ]);

        $response = $this->actingAs($viewer, 'web')
            ->get(route('tenant.trades.chat.show', ['tenant' => $tenant->id, 'channel' => $channel->id]));

        $response->assertOk();
        $channels = $response->viewData('channels');
        $this->assertTrue((bool) ($channels->first()?->is_unread ?? false));

        $this->assertDatabaseHas('chat_reads', [
            'channel_id' => $channel->id,
            'user_id' => $viewer->id,
        ]);
    }

    public function test_dm_channels_require_membership(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Chat DMs',
            'workspace_type' => 'trades',
        ]);

        $userA = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Alex',
            'last_name' => 'Alpha',
            'username' => 'alex',
            'email' => 'alex@test.local',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $userB = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Brooke',
            'last_name' => 'Beta',
            'username' => 'brooke',
            'email' => 'brooke@test.local',
            'role' => 'employee',
            'password' => Hash::make('password123'),
        ]);

        $userC = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Casey',
            'last_name' => 'Gamma',
            'username' => 'casey',
            'email' => 'casey@test.local',
            'role' => 'employee',
            'password' => Hash::make('password123'),
        ]);

        $dm = ChatChannel::findOrCreateDm($tenant->id, $userA, $userB);

        $this->actingAs($userC, 'web')
            ->get(route('tenant.trades.chat.show', ['tenant' => $tenant->id, 'channel' => $dm->id]))
            ->assertStatus(403);

        $this->actingAs($userA, 'web')
            ->get(route('tenant.trades.chat.show', ['tenant' => $tenant->id, 'channel' => $dm->id]))
            ->assertOk();
    }
}
