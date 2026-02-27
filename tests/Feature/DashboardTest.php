<?php

use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertOk();
});

test('dashboard returns stats with correct estimate counts', function () {
    $user = User::factory()->create();

    Estimate::factory()->count(2)->create(); // 2 drafts
    Estimate::factory()->sent()->create(); // 1 sent
    Estimate::factory()->approved()->create(); // 1 approved
    Estimate::factory()->invoiced()->create(); // 1 invoiced (should NOT count as active)

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('stats.totalEstimates', 5)
            ->where('stats.activeEstimates', 2) // only sent + approved
        );
});

test('dashboard returns invoices this month count', function () {
    $user = User::factory()->create();

    Invoice::factory()->count(3)->create(['issued_at' => now()]);
    Invoice::factory()->create(['issued_at' => now()->subMonth()]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('stats.invoicesThisMonth', 3)
        );
});

test('dashboard returns revenue this month', function () {
    $user = User::factory()->create();

    Invoice::factory()->create(['issued_at' => now(), 'total' => '1500.50']);
    Invoice::factory()->create(['issued_at' => now(), 'total' => '2000.00']);
    Invoice::factory()->create(['issued_at' => now()->subMonth(), 'total' => '9999.99']);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('stats.revenueThisMonth', '3500.50')
        );
});

test('dashboard returns recent estimates with customer and unit', function () {
    $user = User::factory()->create();

    Estimate::factory()->count(3)->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('recentEstimates', 3)
            ->has('recentEstimates.0.customer')
            ->has('recentEstimates.0.unit')
        );
});

test('dashboard limits recent estimates to 10', function () {
    $user = User::factory()->create();

    Estimate::factory()->count(15)->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('recentEstimates', 10)
        );
});

test('dashboard returns recent estimates ordered by newest first', function () {
    $user = User::factory()->create();

    $older = Estimate::factory()->create(['created_at' => now()->subDays(2)]);
    $newer = Estimate::factory()->create(['created_at' => now()]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('recentEstimates.0.id', $newer->id)
            ->where('recentEstimates.1.id', $older->id)
        );
});

test('dashboard returns low stock parts', function () {
    $user = User::factory()->create();

    Part::factory()->lowStock()->create(['name' => 'Low Oil Filter']);
    Part::factory()->create(['stock' => 50, 'min_stock' => 5]); // not low stock

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('lowStockParts', 1)
            ->where('lowStockParts.0.name', 'Low Oil Filter')
        );
});

test('dashboard low stock parts are ordered by most critical first', function () {
    $user = User::factory()->create();

    $lessCritical = Part::factory()->create(['stock' => 4, 'min_stock' => 5, 'name' => 'Less Critical']);
    $mostCritical = Part::factory()->create(['stock' => 0, 'min_stock' => 10, 'name' => 'Most Critical']);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('lowStockParts.0.name', 'Most Critical')
            ->where('lowStockParts.1.name', 'Less Critical')
        );
});

test('dashboard returns empty arrays when no data exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('stats.totalEstimates', 0)
            ->where('stats.activeEstimates', 0)
            ->where('stats.invoicesThisMonth', 0)
            ->where('stats.revenueThisMonth', '0.00')
            ->has('recentEstimates', 0)
            ->has('lowStockParts', 0)
        );
});
