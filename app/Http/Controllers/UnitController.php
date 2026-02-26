<?php

namespace App\Http\Controllers;

use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;

class UnitController extends Controller
{
    public function store(StoreUnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return to_route('customers.show', $request->validated('customer_id'))->with('success', 'Unit added successfully.');
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return to_route('customers.show', $unit->customer_id)->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $customerId = $unit->customer_id;
        $unit->delete();

        return to_route('customers.show', $customerId)->with('success', 'Unit deleted successfully.');
    }
}
