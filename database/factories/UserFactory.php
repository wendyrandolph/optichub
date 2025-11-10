<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last  = $this->faker->lastName();

        return [
            'tenant_id' => 1, // default for testing
            'username'   => Str::slug("{$first}{$last}") . $this->faker->numberBetween(100, 999),
            'email'      => $this->faker->unique()->safeEmail(),
            'first_name' => $first,
            'last_name'  => $last,
            'password'   => static::$password ??= Hash::make('password123'),
            'role'       => 'employee', // default role
            'is_beta' => false,
            'client_id'  => null,
            'must_change_password' => false,
            //'remember_token' => Str::random(10),
        ];
    }

    /**
     * Quick helpers for roles
     */
    public function provider(): static
    {
        return $this->state(fn() => ['role' => 'provider']);
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

    public function employee(): static
    {
        return $this->state(fn() => ['role' => 'employee']);
    }

    public function client(Client $client): static
    {
        return $this->state(fn() => [
            'role' => 'client',
            'client_id' => $client->id,
            'tenant_id' => $client->tenant_id,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
