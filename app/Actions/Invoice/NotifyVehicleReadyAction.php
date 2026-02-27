<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Notifications\VehicleReadyNotification;

class NotifyVehicleReadyAction
{
    public function execute(Invoice $invoice): bool
    {
        if ($invoice->wasNotified()) {
            return false;
        }

        $invoice->load('estimate.customer', 'estimate.unit');

        $customer = $invoice->estimate->customer;

        $customer->notify(new VehicleReadyNotification($invoice));

        $invoice->markAsNotified();

        return true;
    }
}
