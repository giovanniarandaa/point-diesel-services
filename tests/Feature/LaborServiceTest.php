<?php

use App\Models\LaborService;
use App\Models\User;

test('guests cannot access services', function () {
    $this->get(route('services.index'))->assertRedirect(route('login'));
});

test('services index page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('services.index'))->assertOk();
});

test('services index shows service list', function () {
    $user = User::factory()->create();
    LaborService::factory()->count(3)->create();

    $this->actingAs($user)->get(route('services.index'))->assertOk();
});

test('services can be searched by name', function () {
    $user = User::factory()->create();
    LaborService::factory()->create(['name' => 'Oil Change']);
    LaborService::factory()->create(['name' => 'Brake Inspection']);

    $this->actingAs($user)->get(route('services.index', ['search' => 'oil']))->assertOk();
});

test('services are paginated', function () {
    $user = User::factory()->create();
    LaborService::factory()->count(20)->create();

    $this->actingAs($user)->get(route('services.index'))->assertOk();
});

test('create service page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('services.create'))->assertOk();
});

test('service can be created', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('services.store'), [
        'name' => 'Oil Change',
        'description' => 'Complete oil and filter change',
        'default_price' => '75.00',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('labor_services', [
        'name' => 'Oil Change',
        'default_price' => '75.00',
    ]);
});

test('service requires name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('services.store'), [
        'name' => '',
        'default_price' => '75.00',
    ]);

    $response->assertSessionHasErrors('name');
});

test('service requires default price', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('services.store'), [
        'name' => 'Oil Change',
        'default_price' => '',
    ]);

    $response->assertSessionHasErrors('default_price');
});

test('default price must be numeric', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('services.store'), [
        'name' => 'Oil Change',
        'default_price' => 'not-a-number',
    ]);

    $response->assertSessionHasErrors('default_price');
});

test('default price must be non-negative', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('services.store'), [
        'name' => 'Oil Change',
        'default_price' => '-10.00',
    ]);

    $response->assertSessionHasErrors('default_price');
});

test('service show page is displayed', function () {
    $user = User::factory()->create();
    $service = LaborService::factory()->create();

    $this->actingAs($user)->get(route('services.show', $service))->assertOk();
});

test('edit service page is displayed', function () {
    $user = User::factory()->create();
    $service = LaborService::factory()->create();

    $this->actingAs($user)->get(route('services.edit', $service))->assertOk();
});

test('service can be updated', function () {
    $user = User::factory()->create();
    $service = LaborService::factory()->create();

    $response = $this->actingAs($user)->patch(route('services.update', $service), [
        'name' => 'Updated Service',
        'description' => 'Updated description',
        'default_price' => '150.00',
    ]);

    $response->assertRedirect(route('services.show', $service));
    $this->assertDatabaseHas('labor_services', [
        'id' => $service->id,
        'name' => 'Updated Service',
    ]);
});

test('service can be deleted', function () {
    $user = User::factory()->create();
    $service = LaborService::factory()->create();

    $response = $this->actingAs($user)->delete(route('services.destroy', $service));

    $response->assertRedirect(route('services.index'));
    $this->assertDatabaseMissing('labor_services', ['id' => $service->id]);
});
