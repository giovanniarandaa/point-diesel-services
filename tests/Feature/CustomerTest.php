<?php

use App\Models\Customer;
use App\Models\Unit;
use App\Models\User;

test('guests cannot access customers', function () {
    $this->get(route('customers.index'))->assertRedirect(route('login'));
});

test('customers index page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('customers.index'))->assertOk();
});

test('customers index shows customer list', function () {
    $user = User::factory()->create();
    Customer::factory()->count(3)->create();

    $this->actingAs($user)->get(route('customers.index'))->assertOk();
});

test('customers can be searched by name', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'Acme Diesel Corp']);
    Customer::factory()->create(['name' => 'Texas Trucking LLC']);

    $this->actingAs($user)->get(route('customers.index', ['search' => 'acme']))->assertOk();
});

test('customers can be searched by phone', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['phone' => '+12025551234']);
    Customer::factory()->create(['phone' => '+13015559999']);

    $this->actingAs($user)->get(route('customers.index', ['search' => '2025551234']))->assertOk();
});

test('customers can be searched by email', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['email' => 'john@acmediesel.com']);
    Customer::factory()->create(['email' => 'jane@texastrucking.com']);

    $this->actingAs($user)->get(route('customers.index', ['search' => 'acmediesel']))->assertOk();
});

test('customers are paginated', function () {
    $user = User::factory()->create();
    Customer::factory()->count(20)->create();

    $this->actingAs($user)->get(route('customers.index'))->assertOk();
});

test('create customer page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('customers.create'))->assertOk();
});

test('customer can be created with phone and email', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'Test Customer',
        'phone' => '+12345678900',
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'name' => 'Test Customer',
        'phone' => '+12345678900',
        'email' => 'test@example.com',
    ]);
});

test('customer can be created with phone only', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'Phone Only Customer',
        'phone' => '+12345678900',
        'email' => '',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', ['name' => 'Phone Only Customer']);
});

test('customer can be created with email only', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'Email Only Customer',
        'phone' => '',
        'email' => 'email@example.com',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', ['name' => 'Email Only Customer']);
});

test('customer requires name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => '',
        'phone' => '+12345678900',
    ]);

    $response->assertSessionHasErrors('name');
});

test('customer requires at least one contact method', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'No Contact Customer',
        'phone' => '',
        'email' => '',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('phone must be valid e164 format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'Bad Phone Customer',
        'phone' => '1234567890',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('email must be valid', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'Bad Email Customer',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('customer show page is displayed', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->get(route('customers.show', $customer))->assertOk();
});

test('customer show page includes units', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    Unit::factory()->count(3)->create(['customer_id' => $customer->id]);

    $this->actingAs($user)->get(route('customers.show', $customer))->assertOk();
});

test('edit customer page is displayed', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)->get(route('customers.edit', $customer))->assertOk();
});

test('customer can be updated', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->patch(route('customers.update', $customer), [
        'name' => 'Updated Name',
        'phone' => '+19876543210',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect(route('customers.show', $customer));
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Name',
    ]);
});

test('customer can be soft deleted', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});

test('soft deleting customer deletes units', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $unit = Unit::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($user)->delete(route('customers.destroy', $customer));

    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    $this->assertDatabaseMissing('units', ['id' => $unit->id]);
});
