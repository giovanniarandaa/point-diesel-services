<?php

namespace App\Models;

use App\Enums\EstimateStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Estimate extends Model
{
    /** @use HasFactory<\Database\Factories\EstimateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'estimate_number',
        'customer_id',
        'unit_id',
        'status',
        'public_token',
        'subtotal_parts',
        'subtotal_labor',
        'shop_supplies_rate',
        'shop_supplies_amount',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'approved_at',
        'approved_ip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EstimateStatus::class,
            'subtotal_parts' => 'decimal:2',
            'subtotal_labor' => 'decimal:2',
            'shop_supplies_rate' => 'decimal:4',
            'shop_supplies_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<EstimateLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(EstimateLine::class)->orderBy('sort_order');
    }

    public static function generateEstimateNumber(): string
    {
        $last = self::query()
            ->where('estimate_number', 'like', 'EST-%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('estimate_number');

        if ($last === null) {
            return 'EST-0001';
        }

        $number = (int) Str::after($last, 'EST-');

        return 'EST-'.str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT);
    }

    public function canEdit(): bool
    {
        return in_array($this->status, [EstimateStatus::Draft, EstimateStatus::Sent]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => EstimateStatus::Sent,
            'public_token' => Str::uuid()->toString(),
        ]);
    }

    /**
     * @param  Builder<Estimate>  $query
     * @return Builder<Estimate>
     */
    public function scopeByStatus(Builder $query, EstimateStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
