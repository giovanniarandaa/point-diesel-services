<?php

namespace Database\Factories;

use App\Models\Estimate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'estimate_id' => Estimate::factory()->approved(),
            'issued_at' => now(),
            'subtotal_parts' => '250.00',
            'subtotal_labor' => '200.00',
            'shop_supplies_rate' => '0.0500',
            'shop_supplies_amount' => '10.00',
            'tax_rate' => '0.0825',
            'tax_amount' => '37.95',
            'total' => '497.95',
        ];
    }
}
