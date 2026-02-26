<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part>
 */
class PartFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('???-####')),
            'name' => fake()->randomElement([
                'Oil Filter', 'Air Filter', 'Fuel Filter', 'Brake Pad Set',
                'Coolant Hose', 'Turbo Actuator', 'EGR Valve', 'DPF Sensor',
                'Alternator Belt', 'Starter Motor', 'Water Pump', 'Thermostat',
                'Injector Nozzle', 'Glow Plug Set', 'Radiator Cap',
            ]),
            'description' => fake()->optional(0.7)->sentence(),
            'cost' => fake()->randomFloat(2, 5, 500),
            'sale_price' => fake()->randomFloat(2, 10, 800),
            'stock' => fake()->numberBetween(0, 100),
            'min_stock' => fake()->numberBetween(1, 10),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock' => 1,
            'min_stock' => 5,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock' => 0,
            'min_stock' => 5,
        ]);
    }
}
