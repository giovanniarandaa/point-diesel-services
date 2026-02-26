<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property numeric $cost
 * @property numeric $sale_price
 * @property int $stock
 * @property int $min_stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\PartFactory factory($count = null, $state = [])
 * @method static Builder<static>|Part lowStock()
 * @method static Builder<static>|Part newModelQuery()
 * @method static Builder<static>|Part newQuery()
 * @method static Builder<static>|Part query()
 * @method static Builder<static>|Part whereCost($value)
 * @method static Builder<static>|Part whereCreatedAt($value)
 * @method static Builder<static>|Part whereDescription($value)
 * @method static Builder<static>|Part whereId($value)
 * @method static Builder<static>|Part whereMinStock($value)
 * @method static Builder<static>|Part whereName($value)
 * @method static Builder<static>|Part whereSalePrice($value)
 * @method static Builder<static>|Part whereSku($value)
 * @method static Builder<static>|Part whereStock($value)
 * @method static Builder<static>|Part whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Part extends Model
{
    /** @use HasFactory<\Database\Factories\PartFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'description',
        'cost',
        'sale_price',
        'stock',
        'min_stock',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
        ];
    }

    /**
     * @param  Builder<Part>  $query
     * @return Builder<Part>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }
}
