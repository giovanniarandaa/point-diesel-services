<?php

namespace Database\Factories;

use App\Enums\EstimateStatus;
use App\Models\Customer;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Estimate>
 */
class EstimateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customer = Customer::factory()->create();

        return [
            'estimate_number' => 'EST-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'unit_id' => Unit::factory()->create(['customer_id' => $customer->id])->id,
            'status' => EstimateStatus::Draft,
            'notes' => fake()->optional(0.5)->sentence(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EstimateStatus::Sent,
            'public_token' => Str::uuid()->toString(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EstimateStatus::Approved,
            'public_token' => Str::uuid()->toString(),
            'approved_at' => now(),
            'approved_ip' => '127.0.0.1',
        ]);
    }

    public function invoiced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EstimateStatus::Invoiced,
            'public_token' => Str::uuid()->toString(),
            'approved_at' => now()->subDay(),
            'approved_ip' => '127.0.0.1',
        ]);
    }
}
