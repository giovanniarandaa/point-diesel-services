<?php

namespace App\Actions\Invoice;

use App\Enums\EstimateStatus;
use App\Models\Estimate;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class ConvertEstimateToInvoiceAction
{
    public function __construct(private DeductInventoryAction $deductInventory) {}

    /**
     * @return array{invoice: Invoice, warnings: list<array{part_id: int, name: string, sku: string, requested: int, available: int}>}
     */
    public function execute(Estimate $estimate): array
    {
        if ($estimate->status !== EstimateStatus::Approved) {
            abort(422, 'Only approved estimates can be converted to invoices.');
        }

        if ($estimate->invoice !== null) {
            abort(422, 'This estimate already has an invoice.');
        }

        return DB::transaction(function () use ($estimate): array {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'estimate_id' => $estimate->id,
                'issued_at' => now(),
                'subtotal_parts' => $estimate->subtotal_parts,
                'subtotal_labor' => $estimate->subtotal_labor,
                'shop_supplies_rate' => $estimate->getRawOriginal('shop_supplies_rate'),
                'shop_supplies_amount' => $estimate->shop_supplies_amount,
                'tax_rate' => $estimate->getRawOriginal('tax_rate'),
                'tax_amount' => $estimate->tax_amount,
                'total' => $estimate->total,
            ]);

            $warnings = $this->deductInventory->execute($estimate);

            $estimate->markAsInvoiced();

            return [
                'invoice' => $invoice,
                'warnings' => $warnings,
            ];
        });
    }
}
