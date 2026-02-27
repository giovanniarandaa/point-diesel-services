<?php

use App\Models\Setting;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('encargado', 'web');
});

test('guests cannot access business settings', function () {
    $this->get(route('business-settings.edit'))->assertRedirect(route('login'));
});

test('guests cannot update business settings', function () {
    $this->patch(route('business-settings.update'), [])->assertRedirect(route('login'));
});

test('encargado cannot access business settings', function () {
    $user = User::factory()->create();
    $user->assignRole('encargado');

    $this->actingAs($user)
        ->get(route('business-settings.edit'))
        ->assertForbidden();
});

test('encargado cannot update business settings', function () {
    $user = User::factory()->create();
    $user->assignRole('encargado');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '0.0600',
            'tax_rate' => '0.0900',
        ])
        ->assertForbidden();
});

test('admin can access business settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Setting::set('shop_supplies_rate', '0.0500');
    Setting::set('tax_rate', '0.0825');

    $this->actingAs($user)
        ->get(route('business-settings.edit'))
        ->assertOk();
});

test('admin can update business settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Setting::set('shop_supplies_rate', '0.0500');
    Setting::set('tax_rate', '0.0825');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '0.0600',
            'tax_rate' => '0.0900',
        ])
        ->assertRedirect(route('business-settings.edit'))
        ->assertSessionHas('success');

    expect(Setting::get('shop_supplies_rate'))->toBe('0.0600');
    expect(Setting::get('tax_rate'))->toBe('0.0900');
});

test('shop supplies rate validation', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '',
            'tax_rate' => '0.0825',
        ])
        ->assertSessionHasErrors('shop_supplies_rate');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '1.5',
            'tax_rate' => '0.0825',
        ])
        ->assertSessionHasErrors('shop_supplies_rate');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '-0.01',
            'tax_rate' => '0.0825',
        ])
        ->assertSessionHasErrors('shop_supplies_rate');
});

test('tax rate validation', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '0.0500',
            'tax_rate' => '',
        ])
        ->assertSessionHasErrors('tax_rate');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '0.0500',
            'tax_rate' => '1.5',
        ])
        ->assertSessionHasErrors('tax_rate');
});

test('flash success message on update', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Setting::set('shop_supplies_rate', '0.0500');
    Setting::set('tax_rate', '0.0825');

    $this->actingAs($user)
        ->patch(route('business-settings.update'), [
            'shop_supplies_rate' => '0.0600',
            'tax_rate' => '0.0900',
        ])
        ->assertSessionHas('success', 'Business settings updated successfully.');
});
