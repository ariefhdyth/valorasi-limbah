<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'pic_name',
        'daily_capacity_kg',
    ];

    protected $casts = [
        'daily_capacity_kg' => 'decimal:2',
    ];

    /**
     * Get the type label in Indonesian.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'market' => 'Pasar',
            'warehouse' => 'Gudang Sortir',
            'retail' => 'Supermarket/Retail',
            'farm' => 'Lahan Pertanian',
            default => $this->type,
        };
    }

    /**
     * Waste logs recorded at this collection point.
     */
    public function wasteLogs(): HasMany
    {
        return $this->hasMany(WasteLog::class);
    }
}
