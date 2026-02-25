<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'phone' => '+1'.fake()->numerify('##########'),
            'email' => fake()->unique()->companyEmail(),
        ];
    }

    public function withPhoneOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'phone' => '+1'.fake()->numerify('##########'),
            'email' => null,
        ]);
    }

    public function withEmailOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'phone' => null,
            'email' => fake()->unique()->companyEmail(),
        ]);
    }
}
