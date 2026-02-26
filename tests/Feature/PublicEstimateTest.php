<?php

use App\Enums\EstimateStatus;
use App\Models\Estimate;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

test('public estimate can be viewed without authentication', function () {
    $estimate = Estimate::factory()->sent()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('estimate-public')
            ->has('estimate')
            ->has('shopPhone'));
});

test('public estimate view returns 404 for invalid token', function () {
    $this->get(route('estimate.public.show', 'invalid-token-that-does-not-exist'))
        ->assertNotFound();
});

test('public estimate view shows sent estimate with correct data', function () {
    $estimate = Estimate::factory()->sent()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('estimate.estimate_number', $estimate->estimate_number)
            ->where('estimate.status', 'sent')
            ->has('estimate.customer'));
});

test('public estimate view shows approved estimate', function () {
    $estimate = Estimate::factory()->approved()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('estimate.status', 'approved'));
});

test('public estimate view shows invoiced estimate', function () {
    $estimate = Estimate::factory()->invoiced()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('estimate.status', 'invoiced'));
});

test('sent estimate can be approved via public link', function () {
    $estimate = Estimate::factory()->sent()->create();

    $response = $this->post(route('estimate.public.approve', $estimate->public_token));

    $response->assertRedirect(route('estimate.public.show', $estimate->public_token));
    $response->assertSessionHas('success');

    $estimate->refresh();
    expect($estimate->status)->toBe(EstimateStatus::Approved);
    expect($estimate->approved_at)->not->toBeNull();
    expect($estimate->approved_ip)->not->toBeNull();
});

test('approval captures client IP address', function () {
    $estimate = Estimate::factory()->sent()->create();

    $this->post(route('estimate.public.approve', $estimate->public_token));

    $estimate->refresh();
    expect($estimate->approved_ip)->toBe('127.0.0.1');
});

test('already approved estimate approval is idempotent', function () {
    $estimate = Estimate::factory()->approved()->create();
    $originalApprovedAt = $estimate->approved_at->toISOString();

    $this->post(route('estimate.public.approve', $estimate->public_token))
        ->assertRedirect(route('estimate.public.show', $estimate->public_token));

    $estimate->refresh();
    expect($estimate->status)->toBe(EstimateStatus::Approved);
    expect($estimate->approved_at->toISOString())->toBe($originalApprovedAt);
});

test('draft estimate returns 404 on public view', function () {
    $estimate = Estimate::factory()->create([
        'status' => EstimateStatus::Draft,
        'public_token' => Str::uuid()->toString(),
    ]);

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertNotFound();
});

test('draft estimate cannot be approved via public link', function () {
    $estimate = Estimate::factory()->create([
        'status' => EstimateStatus::Draft,
        'public_token' => Str::uuid()->toString(),
    ]);

    $this->post(route('estimate.public.approve', $estimate->public_token))
        ->assertNotFound();

    $estimate->refresh();
    expect($estimate->status)->toBe(EstimateStatus::Draft);
    expect($estimate->approved_at)->toBeNull();
});

test('invoiced estimate approval is idempotent', function () {
    $estimate = Estimate::factory()->invoiced()->create();
    $originalApprovedAt = $estimate->approved_at->toISOString();

    $this->post(route('estimate.public.approve', $estimate->public_token));

    $estimate->refresh();
    expect($estimate->status)->toBe(EstimateStatus::Invoiced);
    expect($estimate->approved_at->toISOString())->toBe($originalApprovedAt);
});

test('approve returns 404 for invalid token', function () {
    $this->post(route('estimate.public.approve', 'invalid-token'))
        ->assertNotFound();
});

test('shop phone is passed to public view from config', function () {
    config(['app.shop_phone' => '+1-555-123-4567']);
    $estimate = Estimate::factory()->sent()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('shopPhone', '+1-555-123-4567'));
});

test('public estimate loads customer and unit relationships', function () {
    $estimate = Estimate::factory()->sent()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('estimate.customer')
            ->has('estimate.unit'));
});

test('public estimate loads lines relationship', function () {
    $estimate = Estimate::factory()->sent()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('estimate.lines'));
});

test('markAsApproved sets status, timestamp and IP', function () {
    $estimate = Estimate::factory()->sent()->create();

    $estimate->markAsApproved('192.168.1.100');
    $estimate->refresh();

    expect($estimate->status)->toBe(EstimateStatus::Approved);
    expect($estimate->approved_at)->not->toBeNull();
    expect($estimate->approved_ip)->toBe('192.168.1.100');
});

test('idempotent approval does not set flash success for new approval', function () {
    $estimate = Estimate::factory()->approved()->create();

    $this->post(route('estimate.public.approve', $estimate->public_token))
        ->assertRedirect()
        ->assertSessionHas('success', 'This estimate has already been approved.');
});

test('idempotent invoiced approval does not set flash success for new approval', function () {
    $estimate = Estimate::factory()->invoiced()->create();

    $this->post(route('estimate.public.approve', $estimate->public_token))
        ->assertRedirect()
        ->assertSessionHas('success', 'This estimate has already been approved.');
});

test('public estimate hides sensitive fields', function () {
    $estimate = Estimate::factory()->sent()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('estimate.approved_ip')
            ->missing('estimate.customer_id')
            ->missing('estimate.unit_id'));
});

test('public estimate customer only returns name', function () {
    $estimate = Estimate::factory()->sent()->create();

    $this->get(route('estimate.public.show', $estimate->public_token))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('estimate.customer.name')
            ->missing('estimate.customer.phone')
            ->missing('estimate.customer.email'));
});
