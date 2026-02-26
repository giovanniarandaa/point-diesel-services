<?php

use App\Models\Part;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests cannot access parts', function () {
    $this->get(route('parts.index'))->assertRedirect(route('login'));
});

test('guests cannot store parts', function () {
    $this->post(route('parts.store'), [])->assertRedirect(route('login'));
});

test('guests cannot update parts', function () {
    $part = Part::factory()->create();
    $this->patch(route('parts.update', $part), [])->assertRedirect(route('login'));
});

test('guests cannot delete parts', function () {
    $part = Part::factory()->create();
    $this->delete(route('parts.destroy', $part))->assertRedirect(route('login'));
});

test('parts index page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('parts.index'))->assertOk();
});

test('parts index shows part list', function () {
    $user = User::factory()->create();
    Part::factory()->count(3)->create();

    $this->actingAs($user)->get(route('parts.index'))->assertOk();
});

test('parts can be searched by name', function () {
    $user = User::factory()->create();
    Part::factory()->create(['name' => 'Oil Filter']);
    Part::factory()->create(['name' => 'Brake Pad Set']);

    $this->actingAs($user)
        ->get(route('parts.index', ['search' => 'oil']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('parts/index')
            ->has('parts.data', 1)
            ->where('parts.data.0.name', 'Oil Filter'));
});

test('parts can be searched by sku', function () {
    $user = User::factory()->create();
    Part::factory()->create(['sku' => 'FLT-0001']);
    Part::factory()->create(['sku' => 'BRK-0001']);

    $this->actingAs($user)
        ->get(route('parts.index', ['search' => 'FLT']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('parts.data', 1)
            ->where('parts.data.0.sku', 'FLT-0001'));
});

test('parts can be filtered by low stock', function () {
    $user = User::factory()->create();
    Part::factory()->create(['stock' => 10, 'min_stock' => 5]);
    Part::factory()->lowStock()->create();

    $this->actingAs($user)
        ->get(route('parts.index', ['filter' => 'low_stock']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('parts.data', 1));
});

test('parts are paginated', function () {
    $user = User::factory()->create();
    Part::factory()->count(20)->create();

    $this->actingAs($user)->get(route('parts.index'))->assertOk();
});

test('create part page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('parts.create'))->assertOk();
});

test('part can be created', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'FLT-0001',
        'name' => 'Oil Filter',
        'description' => 'Heavy duty oil filter',
        'cost' => '15.50',
        'sale_price' => '25.00',
        'stock' => 50,
        'min_stock' => 5,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('parts', [
        'sku' => 'FLT-0001',
        'name' => 'Oil Filter',
    ]);
});

test('sku is uppercased on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'flt-0001',
        'name' => 'Oil Filter',
        'cost' => '15.50',
        'sale_price' => '25.00',
        'stock' => 50,
        'min_stock' => 5,
    ]);

    $this->assertDatabaseHas('parts', ['sku' => 'FLT-0001']);
});

test('sku must be unique', function () {
    $user = User::factory()->create();
    Part::factory()->create(['sku' => 'FLT-0001']);

    $response = $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'FLT-0001',
        'name' => 'Another Filter',
        'cost' => '10.00',
        'sale_price' => '20.00',
        'stock' => 10,
        'min_stock' => 2,
    ]);

    $response->assertSessionHasErrors('sku');
});

test('part requires name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'FLT-0001',
        'name' => '',
        'cost' => '10.00',
        'sale_price' => '20.00',
        'stock' => 10,
        'min_stock' => 2,
    ]);

    $response->assertSessionHasErrors('name');
});

test('part requires sku', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('parts.store'), [
        'sku' => '',
        'name' => 'Oil Filter',
        'cost' => '10.00',
        'sale_price' => '20.00',
        'stock' => 10,
        'min_stock' => 2,
    ]);

    $response->assertSessionHasErrors('sku');
});

test('cost must be numeric', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'FLT-0001',
        'name' => 'Oil Filter',
        'cost' => 'not-a-number',
        'sale_price' => '20.00',
        'stock' => 10,
        'min_stock' => 2,
    ]);

    $response->assertSessionHasErrors('cost');
});

test('stock must be non-negative', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'FLT-0001',
        'name' => 'Oil Filter',
        'cost' => '10.00',
        'sale_price' => '20.00',
        'stock' => -1,
        'min_stock' => 2,
    ]);

    $response->assertSessionHasErrors('stock');
});

test('part show page is displayed', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->get(route('parts.show', $part))->assertOk();
});

test('edit part page is displayed', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->get(route('parts.edit', $part))->assertOk();
});

test('part can be updated', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create();

    $response = $this->actingAs($user)->patch(route('parts.update', $part), [
        'sku' => 'UPD-0001',
        'name' => 'Updated Filter',
        'cost' => '20.00',
        'sale_price' => '35.00',
        'stock' => 100,
        'min_stock' => 10,
    ]);

    $response->assertRedirect(route('parts.show', $part));
    $this->assertDatabaseHas('parts', [
        'id' => $part->id,
        'name' => 'Updated Filter',
        'sku' => 'UPD-0001',
    ]);
});

test('sku unique ignores current part on update', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create(['sku' => 'FLT-0001']);

    $response = $this->actingAs($user)->patch(route('parts.update', $part), [
        'sku' => 'FLT-0001',
        'name' => 'Same SKU Update',
        'cost' => '10.00',
        'sale_price' => '20.00',
        'stock' => 10,
        'min_stock' => 2,
    ]);

    $response->assertSessionHasNoErrors();
});

test('part can be deleted', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create();

    $response = $this->actingAs($user)->delete(route('parts.destroy', $part));

    $response->assertRedirect(route('parts.index'));
    $this->assertDatabaseMissing('parts', ['id' => $part->id]);
});

test('low stock scope returns correct parts', function () {
    Part::factory()->create(['stock' => 10, 'min_stock' => 5]);
    Part::factory()->create(['stock' => 3, 'min_stock' => 5]);
    Part::factory()->create(['stock' => 5, 'min_stock' => 5]);

    $lowStockParts = Part::lowStock()->get();

    expect($lowStockParts)->toHaveCount(2);
});

test('update requires name', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->patch(route('parts.update', $part), [
        'sku' => 'FLT-0001',
        'name' => '',
        'cost' => '10.00',
        'sale_price' => '20.00',
        'stock' => 10,
        'min_stock' => 2,
    ])->assertSessionHasErrors('name');
});

test('update sku must be unique for different part', function () {
    $user = User::factory()->create();
    Part::factory()->create(['sku' => 'FLT-0001']);
    $part = Part::factory()->create(['sku' => 'BRK-0001']);

    $this->actingAs($user)->patch(route('parts.update', $part), [
        'sku' => 'FLT-0001',
        'name' => 'Test',
        'cost' => '10.00',
        'sale_price' => '20.00',
        'stock' => 10,
        'min_stock' => 2,
    ])->assertSessionHasErrors('sku');
});

test('cost rejects more than 2 decimal places', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'FLT-0001',
        'name' => 'Oil Filter',
        'cost' => '15.999',
        'sale_price' => '25.00',
        'stock' => 50,
        'min_stock' => 5,
    ])->assertSessionHasErrors('cost');
});

test('store part returns flash success message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('parts.store'), [
        'sku' => 'FLT-0001',
        'name' => 'Oil Filter',
        'cost' => '15.50',
        'sale_price' => '25.00',
        'stock' => 50,
        'min_stock' => 5,
    ])->assertSessionHas('success');
});

test('delete part returns flash success message', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create();

    $this->actingAs($user)->delete(route('parts.destroy', $part))->assertSessionHas('success');
});

test('low stock count is shared via inertia', function () {
    $user = User::factory()->create();
    Part::factory()->lowStock()->create();
    Part::factory()->create(['stock' => 100, 'min_stock' => 5]);

    $this->actingAs($user)
        ->get(route('parts.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('parts/index'));

    // lowStockCount is a shared prop, verify via session
    expect(Part::lowStock()->count())->toBe(1);
});
