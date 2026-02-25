<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_customers(): void
    {
        $response = $this->get(route('customers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_customers_index_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
    }

    public function test_customers_index_shows_customer_list(): void
    {
        $user = User::factory()->create();
        Customer::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
    }

    public function test_customers_can_be_searched_by_name(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['name' => 'Acme Diesel Corp']);
        Customer::factory()->create(['name' => 'Texas Trucking LLC']);

        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'acme']));

        $response->assertOk();
    }

    public function test_customers_can_be_searched_by_phone(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['phone' => '+12025551234']);
        Customer::factory()->create(['phone' => '+13015559999']);

        $response = $this->actingAs($user)->get(route('customers.index', ['search' => '2025551234']));

        $response->assertOk();
    }

    public function test_customers_can_be_searched_by_email(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['email' => 'john@acmediesel.com']);
        Customer::factory()->create(['email' => 'jane@texastrucking.com']);

        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'acmediesel']));

        $response->assertOk();
    }

    public function test_customers_are_paginated(): void
    {
        $user = User::factory()->create();
        Customer::factory()->count(20)->create();

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
    }

    public function test_create_customer_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('customers.create'));

        $response->assertOk();
    }

    public function test_customer_can_be_created_with_phone_and_email(): void
    {
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
    }

    public function test_customer_can_be_created_with_phone_only(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Phone Only Customer',
            'phone' => '+12345678900',
            'email' => '',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', ['name' => 'Phone Only Customer']);
    }

    public function test_customer_can_be_created_with_email_only(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Email Only Customer',
            'phone' => '',
            'email' => 'email@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', ['name' => 'Email Only Customer']);
    }

    public function test_customer_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => '',
            'phone' => '+12345678900',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_customer_requires_at_least_one_contact_method(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'No Contact Customer',
            'phone' => '',
            'email' => '',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_phone_must_be_valid_e164_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Bad Phone Customer',
            'phone' => '1234567890',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_email_must_be_valid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Bad Email Customer',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_customer_show_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
    }

    public function test_customer_show_page_includes_units(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        Unit::factory()->count(3)->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
    }

    public function test_edit_customer_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->get(route('customers.edit', $customer));

        $response->assertOk();
    }

    public function test_customer_can_be_updated(): void
    {
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
    }

    public function test_customer_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_soft_deleting_customer_deletes_units(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $unit = Unit::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($user)->delete(route('customers.destroy', $customer));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }
}
