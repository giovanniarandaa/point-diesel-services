<?php

namespace App\Actions\Invoice;

use App\Models\Estimate;
use App\Models\Part;

class DeductInventoryAction
{
    /**
     * @return list<array{part_id: int, name: string, sku: string, requested: int, available: int}>
     */
    public function execute(Estimate $estimate): array
    {
        $estimate->load('lines');
        $warnings = [];

        foreach ($estimate->lines as $line) {
            if ($line->lineable_type !== Part::class) {
                continue;
            }

            /** @var Part|null $part */
            $part = Part::find($line->lineable_id);

            if ($part === null) {
                continue;
            }

            if ($part->stock < $line->quantity) {
                $warnings[] = [
                    'part_id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku,
                    'requested' => $line->quantity,
                    'available' => $part->stock,
                ];
            }

            $part->decrement('stock', $line->quantity);
        }

        return $warnings;
    }
}
