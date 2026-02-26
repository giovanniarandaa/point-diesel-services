<?php

use App\Enums\EstimateStatus;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\LaborService;
use App\Models\Part;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

// Guest protection
test('guests cannot access estimates', function () {
    $this->get(route('estimates.index'))->assertRedirect(route('login'));
});

test('guests cannot store estimates', function () {
    $this->post(route('estimates.store'), [])->assertRedirect(route('login'));
});

test('guests cannot update estimates', function () {
    $estimate = Estimate::factory()->create();
    $this->put(route('estimates.update', $estimate), [])->assertRedirect(route('login'));
});

test('guests cannot delete estimates', function () {
    $estimate = Estimate::factory()->create();
    $this->delete(route('estimates.destroy', $estimate))->assertRedirect(route('login'));
});

// Index
test('estimates index page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('estimates.index'))->assertOk();
});

test('estimates index shows estimate list', function () {
    $user = User::factory()->create();
    Estimate::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('estimates.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('estimates/index')
            ->has('estimates.data', 3));
});

test('estimates can be searched by estimate number', function () {
    $user = User::factory()->create();
    Estimate::factory()->create(['estimate_number' => 'EST-0001']);
    Estimate::factory()->create(['estimate_number' => 'EST-0002']);

    $this->actingAs($user)
        ->get(route('estimates.index', ['search' => 'EST-0001']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('estimates.data', 1)
            ->where('estimates.data.0.estimate_number', 'EST-0001'));
});

test('estimates can be searched by customer name', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['name' => 'John Doe']);
    Estimate::factory()->create(['customer_id' => $customer->id]);
    Estimate::factory()->create();

    $this->actingAs($user)
        ->get(route('estimates.index', ['search' => 'John']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('estimates.data', 1));
});

test('estimates can be filtered by status', function () {
    $user = User::factory()->create();
    Estimate::factory()->create(['status' => EstimateStatus::Draft]);
    Estimate::factory()->sent()->create();

    $this->actingAs($user)
        ->get(route('estimates.index', ['status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('estimates.data', 1)
            ->where('estimates.data.0.status', 'draft'));
});

// Create
test('create estimate page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('estimates.create'))->assertOk();
});

test('estimate can be created with lines', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $part = Part::factory()->create(['sale_price' => '100.00']);
    $service = LaborService::factory()->create(['default_price' => '200.00']);

    $response = $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'unit_id' => null,
        'notes' => 'Test estimate',
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => $part->name,
                'quantity' => 2,
                'unit_price' => '100.00',
            ],
            [
                'lineable_type' => 'LaborService',
                'lineable_id' => $service->id,
                'description' => $service->name,
                'quantity' => 1,
                'unit_price' => '200.00',
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $estimate = Estimate::first();
    expect($estimate)->not->toBeNull();
    expect($estimate->estimate_number)->toStartWith('EST-');
    expect($estimate->lines)->toHaveCount(2);
    expect($estimate->status)->toBe(EstimateStatus::Draft);
});

test('estimate number is sequential', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $part = Part::factory()->create();

    $lineData = [
        [
            'lineable_type' => 'Part',
            'lineable_id' => $part->id,
            'description' => $part->name,
            'quantity' => 1,
            'unit_price' => '50.00',
        ],
    ];

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => $lineData,
    ]);

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => $lineData,
    ]);

    $estimates = Estimate::orderBy('id')->get();
    expect($estimates[0]->estimate_number)->toBe('EST-0001');
    expect($estimates[1]->estimate_number)->toBe('EST-0002');
});

test('estimate totals are calculated correctly', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $part = Part::factory()->create(['sale_price' => '100.00']);
    $service = LaborService::factory()->create(['default_price' => '200.00']);

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => $part->name,
                'quantity' => 2,
                'unit_price' => '100.00',
            ],
            [
                'lineable_type' => 'LaborService',
                'lineable_id' => $service->id,
                'description' => $service->name,
                'quantity' => 1,
                'unit_price' => '200.00',
            ],
        ],
    ]);

    $estimate = Estimate::first();
    // Parts: 2 * 100 = 200
    expect($estimate->subtotal_parts)->toBe('200.00');
    // Labor: 1 * 200 = 200
    expect($estimate->subtotal_labor)->toBe('200.00');
    // Shop supplies: 200 * 0.05 = 10
    expect($estimate->shop_supplies_amount)->toBe('10.00');
    // Tax: (200 + 200 + 10) * 0.0825 = 33.83
    expect($estimate->tax_amount)->toBe('33.82');
    // Total: 200 + 200 + 10 + 33.82 = 443.82
    expect($estimate->total)->toBe('443.82');
});

// Validation
test('estimate requires customer_id', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => '10.00',
            ],
        ],
    ])->assertSessionHasErrors('customer_id');
});

test('estimate requires at least one line', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [],
    ])->assertSessionHasErrors('lines');
});

test('estimate line requires valid lineable type', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [
            [
                'lineable_type' => 'InvalidType',
                'lineable_id' => 1,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => '10.00',
            ],
        ],
    ])->assertSessionHasErrors('lines.0.lineable_type');
});

test('estimate line quantity must be at least 1', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => 'Test',
                'quantity' => 0,
                'unit_price' => '10.00',
            ],
        ],
    ])->assertSessionHasErrors('lines.0.quantity');
});

test('estimate line unit_price rejects more than 2 decimal places', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => '10.999',
            ],
        ],
    ])->assertSessionHasErrors('lines.0.unit_price');
});

// Show
test('estimate show page is displayed', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->create();

    $this->actingAs($user)->get(route('estimates.show', $estimate))->assertOk();
});

// Edit
test('edit estimate page is displayed for draft', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->create(['status' => EstimateStatus::Draft]);

    $this->actingAs($user)->get(route('estimates.edit', $estimate))->assertOk();
});

test('edit estimate redirects for approved estimates', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->approved()->create();

    $this->actingAs($user)
        ->get(route('estimates.edit', $estimate))
        ->assertRedirect(route('estimates.show', $estimate));
});

// Update
test('estimate can be updated', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->create(['status' => EstimateStatus::Draft]);
    $part = Part::factory()->create(['sale_price' => '50.00']);

    $response = $this->actingAs($user)->put(route('estimates.update', $estimate), [
        'customer_id' => $estimate->customer_id,
        'notes' => 'Updated notes',
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => $part->name,
                'quantity' => 3,
                'unit_price' => '50.00',
            ],
        ],
    ]);

    $response->assertRedirect(route('estimates.show', $estimate));
    $response->assertSessionHas('success');

    $estimate->refresh();
    expect($estimate->notes)->toBe('Updated notes');
    expect($estimate->lines)->toHaveCount(1);
    expect($estimate->subtotal_parts)->toBe('150.00');
});

test('approved estimate cannot be updated', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->approved()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->put(route('estimates.update', $estimate), [
        'customer_id' => $estimate->customer_id,
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => '10.00',
            ],
        ],
    ])->assertForbidden();
});

// Delete
test('estimate can be deleted', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->create();

    $response = $this->actingAs($user)->delete(route('estimates.destroy', $estimate));

    $response->assertRedirect(route('estimates.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('estimates', ['id' => $estimate->id]);
});

// Send
test('draft estimate can be sent', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->create(['status' => EstimateStatus::Draft]);

    $response = $this->actingAs($user)->post(route('estimates.send', $estimate));

    $response->assertRedirect(route('estimates.show', $estimate));
    $response->assertSessionHas('success');

    $estimate->refresh();
    expect($estimate->status)->toBe(EstimateStatus::Sent);
    expect($estimate->public_token)->not->toBeNull();
});

test('sent estimate cannot be sent again', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->sent()->create();

    $this->actingAs($user)->post(route('estimates.send', $estimate));

    $estimate->refresh();
    expect($estimate->status)->toBe(EstimateStatus::Sent);
});

// Flash messages
test('store estimate returns flash success message', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => $part->id,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => '10.00',
            ],
        ],
    ])->assertSessionHas('success');
});

test('delete estimate returns flash success message', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->create();

    $this->actingAs($user)->delete(route('estimates.destroy', $estimate))->assertSessionHas('success');
});

// Fix #1: lineable_id existence validation
test('estimate line rejects non-existent part id', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [
            [
                'lineable_type' => 'Part',
                'lineable_id' => 99999,
                'description' => 'Ghost part',
                'quantity' => 1,
                'unit_price' => '10.00',
            ],
        ],
    ])->assertSessionHasErrors('lines.0.lineable_id');
});

test('estimate line rejects non-existent service id', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('estimates.store'), [
        'customer_id' => $customer->id,
        'lines' => [
            [
                'lineable_type' => 'LaborService',
                'lineable_id' => 99999,
                'description' => 'Ghost service',
                'quantity' => 1,
                'unit_price' => '50.00',
            ],
        ],
    ])->assertSessionHasErrors('lines.0.lineable_id');
});

// Fix #4: approved/invoiced estimates cannot be deleted
test('approved estimate cannot be deleted', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->approved()->create();

    $this->actingAs($user)
        ->delete(route('estimates.destroy', $estimate))
        ->assertRedirect(route('estimates.show', $estimate));

    $this->assertDatabaseHas('estimates', ['id' => $estimate->id]);
});

test('invoiced estimate cannot be deleted', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->invoiced()->create();

    $this->actingAs($user)
        ->delete(route('estimates.destroy', $estimate))
        ->assertRedirect(route('estimates.show', $estimate));

    $this->assertDatabaseHas('estimates', ['id' => $estimate->id]);
});
