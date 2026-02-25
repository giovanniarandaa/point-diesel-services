<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $validChars = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';
        $vin = '';
        for ($i = 0; $i < 17; $i++) {
            $vin .= $validChars[random_int(0, strlen($validChars) - 1)];
        }

        return [
            'customer_id' => Customer::factory(),
            'vin' => $vin,
            'make' => fake()->randomElement(['Freightliner', 'Peterbilt', 'Kenworth', 'Volvo', 'Mack', 'International']),
            'model' => fake()->randomElement(['Cascadia', '579', 'T680', 'VNL', 'Anthem', 'LT']),
            'engine' => fake()->randomElement(['Detroit DD15', 'Cummins X15', 'PACCAR MX-13', null]),
            'mileage' => fake()->numberBetween(0, 500000),
        ];
    }
}
