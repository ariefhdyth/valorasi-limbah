<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commodity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'market_price_per_kg',
    ];

    protected $casts = [
        'market_price_per_kg' => 'decimal:2',
    ];

    /**
     * Get the category label in Indonesian.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'leafy' => 'Sayur Daun',
            'fruit_veg' => 'Sayur Buah',
            'root' => 'Umbi/Akar',
            'stem' => 'Batang/Tangkai',
            default => $this->category,
        };
    }

    /**
     * Waste logs associated with this commodity.
     */
    public function wasteLogs(): HasMany
    {
        return $this->hasMany(WasteLog::class);
    }
}
