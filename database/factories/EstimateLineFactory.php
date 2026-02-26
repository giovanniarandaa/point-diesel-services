<?php

namespace Database\Factories;

use App\Models\Estimate;
use App\Models\LaborService;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EstimateLine>
 */
class EstimateLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isPart = fake()->boolean();
        $lineable = $isPart ? Part::factory()->create() : LaborService::factory()->create();
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = $isPart ? $lineable->sale_price : $lineable->default_price;

        return [
            'estimate_id' => Estimate::factory(),
            'lineable_type' => $lineable::class,
            'lineable_id' => $lineable->id,
            'description' => $lineable->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => bcmul((string) $quantity, (string) $unitPrice, 2),
            'sort_order' => 0,
        ];
    }

    public function forPart(?Part $part = null): static
    {
        return $this->state(function (array $attributes) use ($part): array {
            $part ??= Part::factory()->create();
            $quantity = $attributes['quantity'] ?? fake()->numberBetween(1, 5);

            return [
                'lineable_type' => Part::class,
                'lineable_id' => $part->id,
                'description' => $part->name,
                'unit_price' => $part->sale_price,
                'line_total' => bcmul((string) $quantity, (string) $part->sale_price, 2),
            ];
        });
    }

    public function forService(?LaborService $service = null): static
    {
        return $this->state(function (array $attributes) use ($service): array {
            $service ??= LaborService::factory()->create();
            $quantity = $attributes['quantity'] ?? fake()->numberBetween(1, 5);

            return [
                'lineable_type' => LaborService::class,
                'lineable_id' => $service->id,
                'description' => $service->name,
                'unit_price' => $service->default_price,
                'line_total' => bcmul((string) $quantity, (string) $service->default_price, 2),
            ];
        });
    }
}
