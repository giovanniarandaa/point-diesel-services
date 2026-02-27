<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int $estimate_id
 * @property \Illuminate\Support\Carbon $issued_at
 * @property numeric $subtotal_parts
 * @property numeric $subtotal_labor
 * @property numeric $shop_supplies_rate
 * @property numeric $shop_supplies_amount
 * @property numeric $tax_rate
 * @property numeric $tax_amount
 * @property numeric $total
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Estimate $estimate
 *
 * @method static \Database\Factories\InvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 *
 * @mixin \Eloquent
 */
class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'estimate_id',
        'issued_at',
        'subtotal_parts',
        'subtotal_labor',
        'shop_supplies_rate',
        'shop_supplies_amount',
        'tax_rate',
        'tax_amount',
        'total',
        'notified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'subtotal_parts' => 'decimal:2',
            'subtotal_labor' => 'decimal:2',
            'shop_supplies_rate' => 'decimal:4',
            'shop_supplies_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Estimate, $this>
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $last = self::query()
            ->where('invoice_number', 'like', 'INV-%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('invoice_number');

        if ($last === null) {
            return 'INV-0001';
        }

        $number = (int) Str::after($last, 'INV-');

        return 'INV-'.str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT);
    }

    public function markAsNotified(): void
    {
        $this->update([
            'notified_at' => now(),
        ]);
    }

    public function wasNotified(): bool
    {
        return $this->notified_at !== null;
    }
}
