<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_inbox_denies_without_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'provider_employee',
            'can_manage_support' => false,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/provider/support')
            ->assertStatus(403);
    }

    public function test_support_inbox_allows_with_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'provider_employee',
            'can_manage_support' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/provider/support')
            ->assertStatus(200);
    }
}
