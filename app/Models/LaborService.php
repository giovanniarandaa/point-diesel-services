<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property numeric $default_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\LaborServiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService whereDefaultPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaborService whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class LaborService extends Model
{
    /** @use HasFactory<\Database\Factories\LaborServiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'default_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
        ];
    }
}
