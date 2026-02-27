<?php

use App\Models\Customer;
use App\Models\LaborService;
use App\Models\Part;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;

test('new estimate uses current rates from settings', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $unit = Unit::factory()->for($customer)->create();
    $part = Part::factory()->create(['sale_price' => '100.00']);

    Setting::set('shop_supplies_rate', '0.1000');
    Setting::set('tax_rate', '0.0900');

    $this->actingAs($user)
        ->post(route('estimates.store'), [
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'lines' => [
                [
                    'lineable_type' => 'Part',
                    'lineable_id' => $part->id,
                    'description' => $part->name,
                    'quantity' => 1,
                    'unit_price' => '100.00',
                ],
            ],
        ]);

    $estimate = \App\Models\Estimate::query()->latest()->first();

    expect((float) $estimate->getRawOriginal('shop_supplies_rate'))->toBe(0.1);
    expect((float) $estimate->getRawOriginal('tax_rate'))->toBe(0.09);
});

test('changing settings does not affect existing estimates', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $unit = Unit::factory()->for($customer)->create();
    $service = LaborService::factory()->create(['default_price' => '200.00']);

    Setting::set('shop_supplies_rate', '0.0500');
    Setting::set('tax_rate', '0.0825');

    $this->actingAs($user)
        ->post(route('estimates.store'), [
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'lines' => [
                [
                    'lineable_type' => 'LaborService',
                    'lineable_id' => $service->id,
                    'description' => $service->name,
                    'quantity' => 1,
                    'unit_price' => '200.00',
                ],
            ],
        ]);

    $estimate = \App\Models\Estimate::query()->latest()->first();
    expect((float) $estimate->getRawOriginal('shop_supplies_rate'))->toBe(0.05);

    // Change settings
    Setting::set('shop_supplies_rate', '0.1000');

    // Existing estimate still has old rate
    $estimate->refresh();
    expect((float) $estimate->getRawOriginal('shop_supplies_rate'))->toBe(0.05);
});
