<?php

namespace App\Http\Controllers;

use App\Actions\Invoice\ConvertEstimateToInvoiceAction;
use App\Actions\Invoice\NotifyVehicleReadyAction;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Part;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function store(Estimate $estimate, ConvertEstimateToInvoiceAction $action): RedirectResponse
    {
        $result = $action->execute($estimate);

        /** @var Invoice $invoice */
        $invoice = $result['invoice'];

        $message = 'Invoice created successfully.';

        if (count($result['warnings']) > 0) {
            $items = array_map(fn (array $w): string => $w['name'].' ('.$w['sku'].')', $result['warnings']);
            $message .= ' Warning: Stock went negative for: '.implode(', ', $items).'.';
        }

        return to_route('invoices.show', $invoice)->with('success', $message);
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'estimate.customer',
            'estimate.unit',
            'estimate.lines',
        ]);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf(Invoice $invoice): HttpResponse
    {
        $invoice->load([
            'estimate.customer',
            'estimate.unit',
            'estimate.lines',
        ]);

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function notify(Invoice $invoice, NotifyVehicleReadyAction $action): RedirectResponse
    {
        $sent = $action->execute($invoice);

        if (! $sent) {
            return to_route('invoices.show', $invoice)->with('success', 'Customer was already notified.');
        }

        return to_route('invoices.show', $invoice)->with('success', 'Customer has been notified that the vehicle is ready for pickup.');
    }

    public function stockWarnings(Estimate $estimate): \Illuminate\Http\JsonResponse
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
        }

        return response()->json($warnings);
    }
}
