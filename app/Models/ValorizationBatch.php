<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorizationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'valorization_method_id',
        'start_date',
        'expected_harvest_date',
        'actual_harvest_date',
        'input_organic_kg',
        'estimated_output_kg',
        'actual_output_kg',
        'process_residue_kg',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_harvest_date' => 'date',
        'actual_harvest_date' => 'date',
        'input_organic_kg' => 'decimal:2',
        'estimated_output_kg' => 'decimal:2',
        'actual_output_kg' => 'decimal:2',
        'process_residue_kg' => 'decimal:2',
    ];

    /**
     * Get the status label in Indonesian.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'processing' => 'Sedang Diproses',
            'harvested' => 'Sudah Dipanen',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    /**
     * Get the effective output weight:
     * - actual_output_kg if harvested
     * - estimated_output_kg if still processing
     */
    public function getEffectiveOutputKgAttribute(): float
    {
        if ($this->status === 'harvested' && $this->actual_output_kg !== null) {
            return (float) $this->actual_output_kg;
        }

        return (float) $this->estimated_output_kg;
    }

    /**
     * The valorization method used for this batch.
     */
    public function valorizationMethod(): BelongsTo
    {
        return $this->belongsTo(ValorizationMethod::class);
    }
}
