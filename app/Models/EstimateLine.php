<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $estimate_id
 * @property string $lineable_type
 * @property int $lineable_id
 * @property string $description
 * @property int $quantity
 * @property numeric $unit_price
 * @property numeric $line_total
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Estimate $estimate
 * @property-read \Illuminate\Database\Eloquent\Model $lineable
 *
 * @method static \Database\Factories\EstimateLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereEstimateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereLineTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereLineableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereLineableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class EstimateLine extends Model
{
    /** @use HasFactory<\Database\Factories\EstimateLineFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'estimate_id',
        'lineable_type',
        'lineable_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Estimate, $this>
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function lineable(): MorphTo
    {
        return $this->morphTo();
    }
}
