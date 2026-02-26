<?php

namespace App\Actions\Estimate;

use App\Models\Estimate;
use App\Models\LaborService;
use App\Models\Part;

class CalculateEstimateTotalsAction
{
    public function execute(Estimate $estimate): void
    {
        $estimate->refresh();
        $estimate->load('lines');

        $subtotalParts = '0.00';
        $subtotalLabor = '0.00';

        foreach ($estimate->lines as $line) {
            if ($line->lineable_type === Part::class) {
                $subtotalParts = bcadd($subtotalParts, (string) $line->line_total, 2);
            } elseif ($line->lineable_type === LaborService::class) {
                $subtotalLabor = bcadd($subtotalLabor, (string) $line->line_total, 2);
            }
        }

        $shopSuppliesRate = (string) $estimate->getRawOriginal('shop_supplies_rate');
        $taxRate = (string) $estimate->getRawOriginal('tax_rate');

        $shopSuppliesAmount = bcmul($subtotalLabor, $shopSuppliesRate, 2);

        $taxableAmount = bcadd(bcadd($subtotalParts, $subtotalLabor, 2), $shopSuppliesAmount, 2);
        $taxAmount = bcmul($taxableAmount, $taxRate, 2);

        $total = bcadd($taxableAmount, $taxAmount, 2);

        $estimate->update([
            'subtotal_parts' => $subtotalParts,
            'subtotal_labor' => $subtotalLabor,
            'shop_supplies_amount' => $shopSuppliesAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }
}
