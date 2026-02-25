<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_can_be_created(): void
    {
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
    }

    public function test_vin_is_uppercased_on_create(): void
    {
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
    }

    public function test_vin_must_be_exactly_17_characters(): void
    {
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
    }

    public function test_vin_must_be_alphanumeric_excluding_ioq(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        // VIN with letter I (invalid)
        $response = $this->actingAs($user)->post(route('units.store'), [
            'customer_id' => $customer->id,
            'vin' => 'ABCDEFGHI23456789',
            'make' => 'Freightliner',
            'model' => 'Cascadia',
            'mileage' => 50000,
        ]);

        $response->assertSessionHasErrors('vin');
    }

    public function test_vin_must_be_unique(): void
    {
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
    }

    public function test_customer_id_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('units.store'), [
            'customer_id' => 9999,
            'vin' => 'ABCDEFGH123456789',
            'make' => 'Freightliner',
            'model' => 'Cascadia',
            'mileage' => 50000,
        ]);

        $response->assertSessionHasErrors('customer_id');
    }

    public function test_make_and_model_are_required(): void
    {
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
    }

    public function test_mileage_must_be_non_negative(): void
    {
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
    }

    public function test_unit_can_be_updated(): void
    {
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
        $this->assertSame('Updated Make', $unit->make);
        $this->assertSame(75000, $unit->mileage);
    }

    public function test_vin_unique_ignores_current_unit_on_update(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['vin' => 'ABCDEFGH123456789']);

        $response = $this->actingAs($user)->patch(route('units.update', $unit), [
            'vin' => 'ABCDEFGH123456789',
            'make' => 'Same VIN Make',
            'model' => 'Same VIN Model',
            'mileage' => 80000,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_unit_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();
        $customerId = $unit->customer_id;

        $response = $this->actingAs($user)->delete(route('units.destroy', $unit));

        $response->assertRedirect(route('customers.show', $customerId));
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_guests_cannot_manage_units(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->post(route('units.store'), [
            'customer_id' => $customer->id,
            'vin' => 'ABCDEFGH123456789',
            'make' => 'Freightliner',
            'model' => 'Cascadia',
            'mileage' => 50000,
        ]);

        $response->assertRedirect(route('login'));
    }
}
