<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
  protected $model = Contact::class;

  public function definition(): array
  {
    return [
      'tenant_id' => 1, // default tenant for dev
      'firstName' => $this->faker->firstName(),
      'lastName'  => $this->faker->lastName(),
      'email'     => $this->faker->unique()->safeEmail(),
      'phone'     => $this->faker->optional()->numerify('###-###-####'),
      'status'    => $this->faker->randomElement(['active', 'inactive']),
      'notes'     => $this->faker->optional()->sentence(10),
    ];
  }
}
