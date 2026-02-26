<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
