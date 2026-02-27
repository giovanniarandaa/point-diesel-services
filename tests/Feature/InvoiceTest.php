<?php

use App\Enums\EstimateStatus;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\Invoice;
use App\Models\LaborService;
use App\Models\Part;
use App\Models\User;
use App\Notifications\VehicleReadyNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

// Guest protection
test('guests cannot convert estimates to invoices', function () {
    $estimate = Estimate::factory()->approved()->create();
    $this->post(route('invoices.store', $estimate))->assertRedirect(route('login'));
});

test('guests cannot view invoices', function () {
    $invoice = Invoice::factory()->create();
    $this->get(route('invoices.show', $invoice))->assertRedirect(route('login'));
});

test('guests cannot download invoice pdfs', function () {
    $invoice = Invoice::factory()->create();
    $this->get(route('invoices.pdf', $invoice))->assertRedirect(route('login'));
});

test('guests cannot access stock warnings api', function () {
    $estimate = Estimate::factory()->approved()->create();
    $this->get(route('api.stock-warnings', $estimate))->assertRedirect(route('login'));
});

test('guests cannot notify vehicle ready', function () {
    $invoice = Invoice::factory()->create();
    $this->post(route('invoices.notify', $invoice))->assertRedirect(route('login'));
});

// Convert to Invoice
test('approved estimate can be converted to invoice', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create(['stock' => 10, 'sale_price' => '100.00']);
    $service = LaborService::factory()->create(['default_price' => '200.00']);

    $estimate = Estimate::factory()->approved()->create([
        'subtotal_parts' => '200.00',
        'subtotal_labor' => '200.00',
        'shop_supplies_amount' => '10.00',
        'tax_amount' => '33.83',
        'total' => '443.83',
    ]);

    EstimateLine::factory()->create([
        'estimate_id' => $estimate->id,
        'lineable_type' => Part::class,
        'lineable_id' => $part->id,
        'quantity' => 2,
        'unit_price' => '100.00',
        'line_total' => '200.00',
    ]);

    EstimateLine::factory()->create([
        'estimate_id' => $estimate->id,
        'lineable_type' => LaborService::class,
        'lineable_id' => $service->id,
        'quantity' => 1,
        'unit_price' => '200.00',
        'line_total' => '200.00',
    ]);

    $this->actingAs($user)
        ->post(route('invoices.store', $estimate))
        ->assertRedirect();

    $estimate->refresh();
    expect($estimate->status)->toBe(EstimateStatus::Invoiced);

    $invoice = Invoice::where('estimate_id', $estimate->id)->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->invoice_number)->toStartWith('INV-');
    expect($invoice->total)->toBe('443.83');
});

test('converting estimate deducts part stock', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create(['stock' => 10, 'sale_price' => '50.00']);

    $estimate = Estimate::factory()->approved()->create([
        'subtotal_parts' => '150.00',
        'subtotal_labor' => '0.00',
        'shop_supplies_amount' => '0.00',
        'tax_amount' => '12.38',
        'total' => '162.38',
    ]);

    EstimateLine::factory()->create([
        'estimate_id' => $estimate->id,
        'lineable_type' => Part::class,
        'lineable_id' => $part->id,
        'quantity' => 3,
        'unit_price' => '50.00',
        'line_total' => '150.00',
    ]);

    $this->actingAs($user)
        ->post(route('invoices.store', $estimate))
        ->assertRedirect();

    $part->refresh();
    expect($part->stock)->toBe(7);
});

test('converting estimate allows negative stock with warning', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create(['stock' => 1, 'sale_price' => '50.00']);

    $estimate = Estimate::factory()->approved()->create([
        'subtotal_parts' => '150.00',
        'subtotal_labor' => '0.00',
        'shop_supplies_amount' => '0.00',
        'tax_amount' => '12.38',
        'total' => '162.38',
    ]);

    EstimateLine::factory()->create([
        'estimate_id' => $estimate->id,
        'lineable_type' => Part::class,
        'lineable_id' => $part->id,
        'quantity' => 3,
        'unit_price' => '50.00',
        'line_total' => '150.00',
    ]);

    $response = $this->actingAs($user)
        ->post(route('invoices.store', $estimate));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $part->refresh();
    expect($part->stock)->toBe(-2);
});

test('draft estimate cannot be converted to invoice', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->create(['status' => EstimateStatus::Draft]);

    $this->actingAs($user)
        ->post(route('invoices.store', $estimate))
        ->assertStatus(422);
});

test('sent estimate cannot be converted to invoice', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->sent()->create();

    $this->actingAs($user)
        ->post(route('invoices.store', $estimate))
        ->assertStatus(422);
});

test('estimate cannot be converted to invoice twice', function () {
    $user = User::factory()->create();
    $estimate = Estimate::factory()->approved()->create();
    Invoice::factory()->create(['estimate_id' => $estimate->id]);

    $this->actingAs($user)
        ->post(route('invoices.store', $estimate))
        ->assertStatus(422);
});

// Invoice numbering
test('invoice numbers are sequential', function () {
    $user = User::factory()->create();

    $estimate1 = Estimate::factory()->approved()->create(['subtotal_parts' => '100.00', 'total' => '100.00']);
    $estimate2 = Estimate::factory()->approved()->create(['subtotal_parts' => '200.00', 'total' => '200.00']);

    $this->actingAs($user)->post(route('invoices.store', $estimate1));
    $this->actingAs($user)->post(route('invoices.store', $estimate2));

    $invoices = Invoice::orderBy('id')->get();
    expect($invoices)->toHaveCount(2);
    expect($invoices[0]->invoice_number)->toBe('INV-0001');
    expect($invoices[1]->invoice_number)->toBe('INV-0002');
});

// Show invoice
test('invoice show page is displayed', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/show')
            ->has('invoice'));
});

test('invoice show loads estimate relationships', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/show')
            ->has('invoice.estimate')
            ->has('invoice.estimate.customer'));
});

// PDF download
test('invoice pdf can be downloaded', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('invoices.pdf', $invoice));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

// Stock warnings API
test('stock warnings api returns warnings for insufficient stock', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create(['stock' => 2, 'sale_price' => '50.00']);

    $estimate = Estimate::factory()->approved()->create();

    EstimateLine::factory()->create([
        'estimate_id' => $estimate->id,
        'lineable_type' => Part::class,
        'lineable_id' => $part->id,
        'quantity' => 5,
        'unit_price' => '50.00',
        'line_total' => '250.00',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('api.stock-warnings', $estimate));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment([
        'part_id' => $part->id,
        'name' => $part->name,
        'sku' => $part->sku,
        'requested' => 5,
        'available' => 2,
    ]);
});

test('stock warnings api returns empty array when stock is sufficient', function () {
    $user = User::factory()->create();
    $part = Part::factory()->create(['stock' => 10, 'sale_price' => '50.00']);

    $estimate = Estimate::factory()->approved()->create();

    EstimateLine::factory()->create([
        'estimate_id' => $estimate->id,
        'lineable_type' => Part::class,
        'lineable_id' => $part->id,
        'quantity' => 3,
        'unit_price' => '50.00',
        'line_total' => '150.00',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('api.stock-warnings', $estimate));

    $response->assertOk();
    $response->assertJsonCount(0);
});

test('stock warnings api ignores labor service lines', function () {
    $user = User::factory()->create();
    $service = LaborService::factory()->create();

    $estimate = Estimate::factory()->approved()->create();

    EstimateLine::factory()->create([
        'estimate_id' => $estimate->id,
        'lineable_type' => LaborService::class,
        'lineable_id' => $service->id,
        'quantity' => 1,
        'unit_price' => '200.00',
        'line_total' => '200.00',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('api.stock-warnings', $estimate));

    $response->assertOk();
    $response->assertJsonCount(0);
});

// Vehicle Ready notification
test('vehicle ready marks invoice as notified', function () {
    Notification::fake();
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)
        ->post(route('invoices.notify', $invoice))
        ->assertRedirect(route('invoices.show', $invoice))
        ->assertSessionHas('success', 'Customer has been notified that the vehicle is ready for pickup.');

    $invoice->refresh();
    expect($invoice->notified_at)->not->toBeNull();
});

test('vehicle ready is idempotent', function () {
    Notification::fake();
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create(['notified_at' => now()]);

    $this->actingAs($user)
        ->post(route('invoices.notify', $invoice))
        ->assertRedirect(route('invoices.show', $invoice))
        ->assertSessionHas('success', 'Customer was already notified.');

    Notification::assertNothingSent();
});

test('vehicle ready dispatches notification to customer', function () {
    Notification::fake();
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();
    $customer = $invoice->estimate->customer;

    $this->actingAs($user)
        ->post(route('invoices.notify', $invoice));

    Notification::assertSentTo($customer, VehicleReadyNotification::class);
});
