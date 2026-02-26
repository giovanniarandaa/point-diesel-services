<?php

namespace App\Models;

use App\Enums\EstimateStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $estimate_number
 * @property int $customer_id
 * @property int|null $unit_id
 * @property EstimateStatus $status
 * @property string|null $public_token
 * @property numeric $subtotal_parts
 * @property numeric $subtotal_labor
 * @property numeric $shop_supplies_rate
 * @property numeric $shop_supplies_amount
 * @property numeric $tax_rate
 * @property numeric $tax_amount
 * @property numeric $total
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approved_ip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EstimateLine> $lines
 * @property-read int|null $lines_count
 * @property-read \App\Models\Unit|null $unit
 *
 * @method static Builder<static>|Estimate byStatus(\App\Enums\EstimateStatus $status)
 * @method static \Database\Factories\EstimateFactory factory($count = null, $state = [])
 * @method static Builder<static>|Estimate newModelQuery()
 * @method static Builder<static>|Estimate newQuery()
 * @method static Builder<static>|Estimate query()
 * @method static Builder<static>|Estimate whereApprovedAt($value)
 * @method static Builder<static>|Estimate whereApprovedIp($value)
 * @method static Builder<static>|Estimate whereCreatedAt($value)
 * @method static Builder<static>|Estimate whereCustomerId($value)
 * @method static Builder<static>|Estimate whereEstimateNumber($value)
 * @method static Builder<static>|Estimate whereId($value)
 * @method static Builder<static>|Estimate whereNotes($value)
 * @method static Builder<static>|Estimate wherePublicToken($value)
 * @method static Builder<static>|Estimate whereShopSuppliesAmount($value)
 * @method static Builder<static>|Estimate whereShopSuppliesRate($value)
 * @method static Builder<static>|Estimate whereStatus($value)
 * @method static Builder<static>|Estimate whereSubtotalLabor($value)
 * @method static Builder<static>|Estimate whereSubtotalParts($value)
 * @method static Builder<static>|Estimate whereTaxAmount($value)
 * @method static Builder<static>|Estimate whereTaxRate($value)
 * @method static Builder<static>|Estimate whereTotal($value)
 * @method static Builder<static>|Estimate whereUnitId($value)
 * @method static Builder<static>|Estimate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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

    public function markAsApproved(string $ip): void
    {
        $this->update([
            'status' => EstimateStatus::Approved,
            'approved_at' => now(),
            'approved_ip' => $ip,
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
