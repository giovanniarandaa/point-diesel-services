<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .header-left { }
        .header-right { text-align: right; }
        .shop-name { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #374151; }
        .invoice-number { font-size: 14px; color: #6b7280; margin-top: 4px; }
        .info-section { margin-bottom: 20px; }
        .info-section table { width: 100%; }
        .info-section td { vertical-align: top; padding: 2px 0; }
        .label { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 12px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th { background: #f3f4f6; padding: 8px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #374151; border-bottom: 2px solid #e5e7eb; }
        .items-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .items-table .text-right { text-align: right; }
        .items-table .type { color: #6b7280; font-size: 10px; }
        .totals { width: 300px; margin-left: auto; margin-top: 20px; }
        .totals table { width: 100%; }
        .totals td { padding: 4px 0; }
        .totals .label-col { color: #6b7280; }
        .totals .value-col { text-align: right; }
        .totals .total-row td { font-size: 16px; font-weight: bold; padding-top: 8px; border-top: 2px solid #1a1a1a; }
        .footer { margin-top: 40px; text-align: center; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="vertical-align: top;">
                <div class="shop-name">{{ config('app.name') }}</div>
                @if(config('app.shop_phone'))
                    <div style="color: #6b7280;">{{ config('app.shop_phone') }}</div>
                @endif
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div style="color: #6b7280; margin-top: 4px;">Date: {{ $invoice->issued_at->format('M d, Y') }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="vertical-align: top; width: 50%;">
                <div class="label">Bill To</div>
                <div class="value" style="font-weight: bold; margin-top: 4px;">{{ $invoice->estimate->customer->name }}</div>
                @if($invoice->estimate->customer->phone)
                    <div class="value">{{ $invoice->estimate->customer->phone }}</div>
                @endif
                @if($invoice->estimate->customer->email)
                    <div class="value">{{ $invoice->estimate->customer->email }}</div>
                @endif
            </td>
            <td style="vertical-align: top; width: 50%;">
                @if($invoice->estimate->unit)
                    <div class="label">Vehicle</div>
                    <div class="value" style="margin-top: 4px;">{{ $invoice->estimate->unit->make }} {{ $invoice->estimate->unit->model }}</div>
                    <div class="value" style="color: #6b7280;">VIN: {{ $invoice->estimate->unit->vin }}</div>
                    @if($invoice->estimate->unit->mileage)
                        <div class="value" style="color: #6b7280;">Mileage: {{ number_format($invoice->estimate->unit->mileage) }}</div>
                    @endif
                @endif
            </td>
        </tr>
    </table>

    <div style="color: #6b7280; font-size: 11px; margin-bottom: 4px;">Estimate: {{ $invoice->estimate->estimate_number }}</div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->estimate->lines as $line)
                <tr>
                    <td class="type">
                        {{ $line->lineable_type === 'App\\Models\\Part' ? 'Part' : 'Labor' }}
                    </td>
                    <td>{{ $line->description }}</td>
                    <td class="text-right">{{ $line->quantity }}</td>
                    <td class="text-right">${{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label-col">Subtotal Parts</td>
                <td class="value-col">${{ number_format((float) $invoice->subtotal_parts, 2) }}</td>
            </tr>
            <tr>
                <td class="label-col">Subtotal Labor</td>
                <td class="value-col">${{ number_format((float) $invoice->subtotal_labor, 2) }}</td>
            </tr>
            <tr>
                <td class="label-col">Shop Supplies ({{ number_format((float) $invoice->shop_supplies_rate * 100, 0) }}%)</td>
                <td class="value-col">${{ number_format((float) $invoice->shop_supplies_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label-col">Tax ({{ number_format((float) $invoice->tax_rate * 100, 2) }}%)</td>
                <td class="value-col">${{ number_format((float) $invoice->tax_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td class="value-col">${{ number_format((float) $invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->estimate->notes)
        <div style="margin-top: 30px; padding: 12px; background: #f9fafb; border-radius: 4px;">
            <div class="label" style="margin-bottom: 4px;">Notes</div>
            <div class="value">{{ $invoice->estimate->notes }}</div>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
    </div>
</body>
</html>
