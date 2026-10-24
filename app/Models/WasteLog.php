<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_date',
        'collection_point_id',
        'commodity_id',
        'total_supply_kg',
        'waste_weight_kg',
        'organic_sortable_kg',
        'inorganic_residue_kg',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'log_date' => 'date',
        'total_supply_kg' => 'decimal:2',
        'waste_weight_kg' => 'decimal:2',
        'organic_sortable_kg' => 'decimal:2',
        'inorganic_residue_kg' => 'decimal:2',
    ];

    /**
     * The collection point where this waste was recorded.
     */
    public function collectionPoint(): BelongsTo
    {
        return $this->belongsTo(CollectionPoint::class);
    }

    /**
     * The commodity type of this waste.
     */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    /**
     * The user who recorded this log.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
