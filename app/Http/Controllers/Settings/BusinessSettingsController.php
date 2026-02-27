<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBusinessSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/business', [
            'shop_supplies_rate' => Setting::get('shop_supplies_rate', '0.0500'),
            'tax_rate' => Setting::get('tax_rate', '0.0825'),
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Setting::set('shop_supplies_rate', $validated['shop_supplies_rate']);
        Setting::set('tax_rate', $validated['tax_rate']);

        return to_route('business-settings.edit')->with('success', 'Business settings updated successfully.');
    }
}
