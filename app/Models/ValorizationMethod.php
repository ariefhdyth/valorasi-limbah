<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValorizationMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_name',
        'default_conversion_rate',
        'product_price_per_kg',
        'duration_days',
    ];

    protected $casts = [
        'default_conversion_rate' => 'decimal:4',
        'product_price_per_kg' => 'decimal:2',
        'duration_days' => 'integer',
    ];

    /**
     * Valorization batches using this method.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ValorizationBatch::class);
    }
}
