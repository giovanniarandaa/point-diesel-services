<?php

use App\Models\LaborService;
use App\Models\Part;
use App\Models\User;

test('guests cannot access catalog search', function () {
    $this->get(route('api.catalog-search', ['q' => 'oil']))->assertRedirect(route('login'));
});

test('catalog search returns parts and services', function () {
    $user = User::factory()->create();
    Part::factory()->create(['name' => 'Oil Filter', 'sku' => 'FLT-001']);
    LaborService::factory()->create(['name' => 'Oil Change']);

    $response = $this->actingAs($user)->getJson(route('api.catalog-search', ['q' => 'oil']));

    $response->assertOk();
    $response->assertJsonCount(1, 'parts');
    $response->assertJsonCount(1, 'services');
    $response->assertJsonPath('parts.0.type', 'Part');
    $response->assertJsonPath('services.0.type', 'LaborService');
});

test('catalog search returns empty for short queries', function () {
    $user = User::factory()->create();
    Part::factory()->create(['name' => 'Oil Filter']);

    $response = $this->actingAs($user)->getJson(route('api.catalog-search', ['q' => 'o']));

    $response->assertOk();
    $response->assertJson([]);
});

test('catalog search can find parts by sku', function () {
    $user = User::factory()->create();
    Part::factory()->create(['name' => 'Oil Filter', 'sku' => 'FLT-001']);

    $response = $this->actingAs($user)->getJson(route('api.catalog-search', ['q' => 'FLT']));

    $response->assertOk();
    $response->assertJsonCount(1, 'parts');
});

test('catalog search limits results to 10 per type', function () {
    $user = User::factory()->create();
    Part::factory()->count(15)->create(['name' => 'Test Part']);

    $response = $this->actingAs($user)->getJson(route('api.catalog-search', ['q' => 'Test']));

    $response->assertOk();
    expect(count($response->json('parts')))->toBeLessThanOrEqual(10);
});
