<?php

use App\Models\Customer;
use App\Models\Unit;
use App\Models\User;

test('unit can be created', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'engine' => 'DD15',
        'mileage' => 50000,
    ]);

    $response->assertRedirect(route('customers.show', $customer));
    $this->assertDatabaseHas('units', [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Freightliner',
    ]);
});

test('vin is uppercased on create', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'abcdefgh123456789',
        'make' => 'Peterbilt',
        'model' => '579',
        'mileage' => 10000,
    ]);

    $this->assertDatabaseHas('units', ['vin' => 'ABCDEFGH123456789']);
});

test('vin must be exactly 17 characters', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'SHORT',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'mileage' => 50000,
    ]);

    $response->assertSessionHasErrors('vin');
});

test('vin must be alphanumeric excluding ioq', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGHI23456789',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'mileage' => 50000,
    ]);

    $response->assertSessionHasErrors('vin');
});

test('vin must be unique', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    Unit::factory()->create(['vin' => 'ABCDEFGH123456789']);

    $response = $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'mileage' => 50000,
    ]);

    $response->assertSessionHasErrors('vin');
});

test('customer id must exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => 9999,
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'mileage' => 50000,
    ]);

    $response->assertSessionHasErrors('customer_id');
});

test('make and model are required', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGH123456789',
        'make' => '',
        'model' => '',
        'mileage' => 50000,
    ]);

    $response->assertSessionHasErrors(['make', 'model']);
});

test('mileage must be non negative', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'mileage' => -100,
    ]);

    $response->assertSessionHasErrors('mileage');
});

test('unit can be updated', function () {
    $user = User::factory()->create();
    $unit = Unit::factory()->create();

    $response = $this->actingAs($user)->patch(route('units.update', $unit), [
        'vin' => $unit->vin,
        'make' => 'Updated Make',
        'model' => 'Updated Model',
        'engine' => 'Updated Engine',
        'mileage' => 75000,
    ]);

    $response->assertRedirect(route('customers.show', $unit->customer_id));
    $unit->refresh();
    expect($unit->make)->toBe('Updated Make');
    expect($unit->mileage)->toBe(75000);
});

test('vin unique ignores current unit on update', function () {
    $user = User::factory()->create();
    $unit = Unit::factory()->create(['vin' => 'ABCDEFGH123456789']);

    $response = $this->actingAs($user)->patch(route('units.update', $unit), [
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Same VIN Make',
        'model' => 'Same VIN Model',
        'mileage' => 80000,
    ]);

    $response->assertSessionHasNoErrors();
});

test('unit can be deleted', function () {
    $user = User::factory()->create();
    $unit = Unit::factory()->create();
    $customerId = $unit->customer_id;

    $response = $this->actingAs($user)->delete(route('units.destroy', $unit));

    $response->assertRedirect(route('customers.show', $customerId));
    $this->assertDatabaseMissing('units', ['id' => $unit->id]);
});

test('guests cannot store units', function () {
    $customer = Customer::factory()->create();

    $this->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'mileage' => 50000,
    ])->assertRedirect(route('login'));
});

test('guests cannot update units', function () {
    $unit = Unit::factory()->create();
    $this->patch(route('units.update', $unit), [])->assertRedirect(route('login'));
});

test('guests cannot delete units', function () {
    $unit = Unit::factory()->create();
    $this->delete(route('units.destroy', $unit))->assertRedirect(route('login'));
});

test('store unit returns flash success message', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('units.store'), [
        'customer_id' => $customer->id,
        'vin' => 'ABCDEFGH123456789',
        'make' => 'Freightliner',
        'model' => 'Cascadia',
        'mileage' => 50000,
    ])->assertSessionHas('success');
});

test('delete unit returns flash success message', function () {
    $user = User::factory()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($user)->delete(route('units.destroy', $unit))->assertSessionHas('success');
});
