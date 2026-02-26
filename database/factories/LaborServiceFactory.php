<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LaborService>
 */
class LaborServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Oil Change', 'Brake Inspection', 'Tire Rotation',
                'Engine Diagnostic', 'Transmission Service', 'Coolant Flush',
                'A/C Recharge', 'Wheel Alignment', 'DPF Regeneration',
                'Turbo Repair', 'Electrical Diagnostic', 'Suspension Repair',
            ]),
            'description' => fake()->optional(0.7)->sentence(),
            'default_price' => fake()->randomFloat(2, 50, 500),
        ];
    }
}
