<?php

namespace App\Services;

use App\Models\ValorizationBatch;
use App\Models\ValorizationMethod;
use App\Models\WasteLog;
use Illuminate\Support\Carbon;

class ValorizationCalculatorService
{
    protected ?string $startDate = null;
    protected ?string $endDate = null;
    protected ?int $collectionPointId = null;

    /**
     * Set the date range filter.
     */
    public function setDateRange(?string $startDate, ?string $endDate): self
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Set the collection point filter.
     */
    public function setCollectionPoint(?int $collectionPointId): self
    {
        $this->collectionPointId = $collectionPointId;

        return $this;
    }

    /**
     * Apply common filters to waste log queries.
     */
    protected function baseWasteLogQuery()
    {
        $query = WasteLog::query();

        if ($this->startDate) {
            $query->where('log_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('log_date', '<=', $this->endDate);
        }

        if ($this->collectionPointId) {
            $query->where('collection_point_id', $this->collectionPointId);
        }

        return $query;
    }

    /**
     * Apply date filters to valorization batch queries.
     * Batches are filtered by start_date within the date range.
     */
    protected function baseBatchQuery()
    {
        $query = ValorizationBatch::query()
            ->whereIn('status', ['processing', 'harvested']);

        if ($this->startDate) {
            $query->where('start_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('start_date', '<=', $this->endDate);
        }

        return $query;
    }

    /**
     * Total Waste Weight (kg)
     */
    public function totalWasteWeight(): float
    {
        return round((float) $this->baseWasteLogQuery()->sum('waste_weight_kg'), 2);
    }

    /**
     * Total Supply Weight (kg)
     */
    public function totalSupplyWeight(): float
    {
        return round((float) $this->baseWasteLogQuery()->sum('total_supply_kg'), 2);
    }

    /**
     * Total Organic Sortable Weight (kg)
     */
    public function totalOrganicSortableWeight(): float
    {
        return round((float) $this->baseWasteLogQuery()->sum('organic_sortable_kg'), 2);
    }

    /**
     * Total Inorganic Residue Weight (kg)
     */
    public function totalInorganicResidueWeight(): float
    {
        return round((float) $this->baseWasteLogQuery()->sum('inorganic_residue_kg'), 2);
    }

    /**
     * 1. Waste Percentage (%)
     *    = (SUM(waste_weight_kg) / SUM(total_supply_kg)) * 100
     */
    public function wastePercentage(): float
    {
        $totals = $this->baseWasteLogQuery()
            ->selectRaw('SUM(waste_weight_kg) as total_waste, SUM(total_supply_kg) as total_supply')
            ->first();

        $totalSupply = (float) ($totals->total_supply ?? 0);
        $totalWaste = (float) ($totals->total_waste ?? 0);

        if ($totalSupply == 0) {
            return 0.0;
        }

        return round(($totalWaste / $totalSupply) * 100, 2);
    }

    /**
     * 2. Waste Accumulation per Collection Point (kg)
     *    Returns an array of ['collection_point_id' => ..., 'name' => ..., 'total_waste_kg' => ...]
     */
    public function wastePerCollectionPoint(): array
    {
        return $this->baseWasteLogQuery()
            ->join('collection_points', 'waste_logs.collection_point_id', '=', 'collection_points.id')
            ->selectRaw('collection_points.id, collection_points.name, collection_points.type, SUM(waste_logs.waste_weight_kg) as total_waste_kg')
            ->groupBy('collection_points.id', 'collection_points.name', 'collection_points.type')
            ->orderByDesc('total_waste_kg')
            ->get()
            ->toArray();
    }

    /**
     * 2b. Bar Chart Data: Waste Weight & Economic Loss per Collection Point
     */
    public function wasteAndLossPerCollectionPoint(): array
    {
        $rows = $this->baseWasteLogQuery()
            ->join('collection_points', 'waste_logs.collection_point_id', '=', 'collection_points.id')
            ->join('commodities', 'waste_logs.commodity_id', '=', 'commodities.id')
            ->selectRaw('
                collection_points.id,
                collection_points.name,
                collection_points.type,
                SUM(waste_logs.waste_weight_kg) as total_waste_kg,
                SUM(waste_logs.total_supply_kg) as total_supply_kg,
                SUM(waste_logs.waste_weight_kg * commodities.market_price_per_kg) as total_loss_rp
            ')
            ->groupBy('collection_points.id', 'collection_points.name', 'collection_points.type')
            ->orderByDesc('total_waste_kg')
            ->get();

        $labels = [];
        $wasteKg = [];
        $lossRp = [];
        $percentages = [];

        foreach ($rows as $row) {
            $labels[] = $row->name;
            $wasteKg[] = round((float) $row->total_waste_kg, 1);
            $lossRp[] = round((float) $row->total_loss_rp, 0);
            $supply = (float) $row->total_supply_kg;
            $percentages[] = $supply > 0 ? round(((float) $row->total_waste_kg / $supply) * 100, 1) : 0.0;
        }

        return [
            'labels' => $labels,
            'waste_kg' => $wasteKg,
            'loss_rp' => $lossRp,
            'percentages' => $percentages,
            'rows' => $rows->toArray(),
        ];
    }

    /**
     * 3. Economic Loss (Rp)
     *    = SUM(waste_logs.waste_weight_kg * commodities.market_price_per_kg)
     */
    public function economicLoss(): float
    {
        $result = $this->baseWasteLogQuery()
            ->join('commodities', 'waste_logs.commodity_id', '=', 'commodities.id')
            ->selectRaw('SUM(waste_logs.waste_weight_kg * commodities.market_price_per_kg) as total_loss')
            ->first();

        return round((float) ($result->total_loss ?? 0), 2);
    }

    /**
     * 4a. Valorized Output Weight (kg)
     *     Uses actual_output_kg if harvested, estimated_output_kg if processing.
     */
    public function valorizedOutputWeight(): float
    {
        $batches = $this->baseBatchQuery()->get();

        $total = $batches->sum(function ($batch) {
            if ($batch->status === 'harvested' && $batch->actual_output_kg !== null) {
                return (float) $batch->actual_output_kg;
            }

            return (float) $batch->estimated_output_kg;
        });

        return round($total, 2);
    }

    /**
     * 4b. Valorized Economic Value (Rp)
     *     = SUM(effective_output_kg * valorization_methods.product_price_per_kg)
     */
    public function valorizedEconomicValue(): float
    {
        $batches = $this->baseBatchQuery()
            ->with('valorizationMethod')
            ->get();

        $total = $batches->sum(function ($batch) {
            $outputKg = ($batch->status === 'harvested' && $batch->actual_output_kg !== null)
                ? (float) $batch->actual_output_kg
                : (float) $batch->estimated_output_kg;

            $pricePerKg = (float) ($batch->valorizationMethod->product_price_per_kg ?? 0);

            return $outputKg * $pricePerKg;
        });

        return round($total, 2);
    }

    /**
     * 5a. Material Circularity Index - CI_Massa (%)
     *     = ((SUM(organic_sortable_kg) - SUM(process_residue_kg)) / SUM(waste_weight_kg)) * 100
     */
    public function materialCircularityIndex(): float
    {
        $wasteTotals = $this->baseWasteLogQuery()
            ->selectRaw('SUM(organic_sortable_kg) as total_organic_sortable, SUM(waste_weight_kg) as total_waste')
            ->first();

        $totalOrganicSortable = (float) ($wasteTotals->total_organic_sortable ?? 0);
        $totalWaste = (float) ($wasteTotals->total_waste ?? 0);

        $totalProcessResidue = (float) ($this->baseBatchQuery()->sum('process_residue_kg'));

        if ($totalWaste == 0) {
            return 0.0;
        }

        $ci = (($totalOrganicSortable - $totalProcessResidue) / $totalWaste) * 100;

        return round(max(0, $ci), 2);
    }

    /**
     * 5b. Economic Recovery Index - CI_Ekonomi (%)
     *     = (Valorized Economic Value / Economic Loss) * 100
     */
    public function economicRecoveryIndex(): float
    {
        $economicLoss = $this->economicLoss();

        if ($economicLoss == 0) {
            return 0.0;
        }

        $valorizedValue = $this->valorizedEconomicValue();

        return round(($valorizedValue / $economicLoss) * 100, 2);
    }

    /**
     * 5c. Composite Circularity Index - CI_Total (%)
     *     = (0.70 * CI_Massa) + (0.30 * CI_Ekonomi)
     */
    public function compositeCircularityIndex(): float
    {
        $ciMassa = $this->materialCircularityIndex();
        $ciEkonomi = $this->economicRecoveryIndex();

        return round((0.70 * $ciMassa) + (0.30 * $ciEkonomi), 2);
    }

    /**
     * Line Chart: Tren Timbulan vs Pengolahan Harian
     * Menggabungkan pasokan masuk, timbulan limbah, fraksi organik, dan input pengolahan biokonversi
     */
    public function dailyWasteVsProcessingTrend(): array
    {
        $wasteDaily = $this->baseWasteLogQuery()
            ->selectRaw('log_date, SUM(total_supply_kg) as supply_kg, SUM(waste_weight_kg) as waste_kg, SUM(organic_sortable_kg) as organic_kg')
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->log_date)->toDateString());

        $batchDaily = $this->baseBatchQuery()
            ->selectRaw('start_date, SUM(input_organic_kg) as input_kg')
            ->groupBy('start_date')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->start_date)->toDateString());

        $allDates = $wasteDaily->keys()->merge($batchDaily->keys())->unique()->sort()->values();

        $categories = [];
        $supplySeries = [];
        $wasteSeries = [];
        $organicSeries = [];
        $processedSeries = [];

        foreach ($allDates as $dateStr) {
            $categories[] = Carbon::parse($dateStr)->format('d M');
            $w = $wasteDaily->get($dateStr);
            $b = $batchDaily->get($dateStr);

            $supplySeries[] = $w ? round((float) $w->supply_kg, 1) : 0.0;
            $wasteSeries[] = $w ? round((float) $w->waste_kg, 1) : 0.0;
            $organicSeries[] = $w ? round((float) $w->organic_kg, 1) : 0.0;
            $processedSeries[] = $b ? round((float) $b->input_kg, 1) : 0.0;
        }

        return [
            'categories' => $categories,
            'supply_series' => $supplySeries,
            'waste_series' => $wasteSeries,
            'organic_series' => $organicSeries,
            'processed_series' => $processedSeries,
        ];
    }

    /**
     * Perbandingan Produksi Kasgot vs Kascing
     */
    public function methodComparison(): array
    {
        $methods = ValorizationMethod::with(['batches' => function ($query) {
            if ($this->startDate) {
                $query->where('start_date', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->where('start_date', '<=', $this->endDate);
            }
            $query->whereIn('status', ['processing', 'harvested']);
        }])->get();

        $items = [];
        $labels = [];
        $inputs = [];
        $outputs = [];
        $values = [];

        foreach ($methods as $method) {
            $batches = $method->batches;
            $inputKg = round((float) $batches->sum('input_organic_kg'), 2);

            $outputKg = round($batches->sum(function ($batch) {
                if ($batch->status === 'harvested' && $batch->actual_output_kg !== null) {
                    return (float) $batch->actual_output_kg;
                }
                return (float) $batch->estimated_output_kg;
            }), 2);

            $economicValue = round($outputKg * (float) $method->product_price_per_kg, 2);
            $actualConversionRate = $inputKg > 0
                ? round(($outputKg / $inputKg) * 100, 2)
                : round((float) $method->default_conversion_rate * 100, 2);

            $items[] = [
                'id' => $method->id,
                'method_name' => $method->name,
                'product_name' => $method->product_name,
                'product_price_per_kg' => (float) $method->product_price_per_kg,
                'duration_days' => $method->duration_days,
                'batch_count' => $batches->count(),
                'total_input_kg' => $inputKg,
                'total_output_kg' => $outputKg,
                'economic_value_rp' => $economicValue,
                'conversion_rate_percent' => $actualConversionRate,
                'default_conversion_rate_percent' => round((float) $method->default_conversion_rate * 100, 2),
            ];

            $labels[] = $method->product_name . ' (' . ($method->product_name === 'Kasgot' ? 'Maggot BSF' : 'Cacing Tanah') . ')';
            $inputs[] = $inputKg;
            $outputs[] = $outputKg;
            $values[] = $economicValue;
        }

        return [
            'items' => $items,
            'chart' => [
                'labels' => $labels,
                'inputs' => $inputs,
                'outputs' => $outputs,
                'values' => $values,
            ],
        ];
    }

    /**
     * Komposisi Timbulan Limbah per Komoditas Sayur
     */
    public function wasteByCommodity(): array
    {
        $rows = $this->baseWasteLogQuery()
            ->join('commodities', 'waste_logs.commodity_id', '=', 'commodities.id')
            ->selectRaw('commodities.name, commodities.category, SUM(waste_logs.waste_weight_kg) as total_kg, SUM(waste_logs.waste_weight_kg * commodities.market_price_per_kg) as total_loss')
            ->groupBy('commodities.name', 'commodities.category')
            ->orderByDesc('total_kg')
            ->get();

        $labels = [];
        $weights = [];
        $losses = [];

        foreach ($rows as $row) {
            $labels[] = $row->name;
            $weights[] = round((float) $row->total_kg, 1);
            $losses[] = round((float) $row->total_loss, 0);
        }

        return [
            'labels' => $labels,
            'weights' => $weights,
            'losses' => $losses,
            'rows' => $rows->toArray(),
        ];
    }

    /**
     * 5 Card KPI Utama dan Data Ringkasan Lengkap
     */
    public function getKpiCards(): array
    {
        $wastePercentage = $this->wastePercentage();
        $totalWaste = $this->totalWasteWeight();
        $totalSupply = $this->totalSupplyWeight();
        $loss = $this->economicLoss();
        $valorizedValue = $this->valorizedEconomicValue();
        $outputWeight = $this->valorizedOutputWeight();
        $ciTotal = $this->compositeCircularityIndex();
        $ciMassa = $this->materialCircularityIndex();
        $ciEkonomi = $this->economicRecoveryIndex();

        return [
            [
                'id' => 'waste_percentage',
                'title' => 'Persentase Limbah Sayur',
                'value' => number_format($wastePercentage, 2, ',', '.') . '%',
                'numeric_value' => $wastePercentage,
                'subtitle' => 'Dari total pasokan ' . number_format($totalSupply, 0, ',', '.') . ' kg',
                'badge' => $wastePercentage > 20 ? 'Tinggi' : ($wastePercentage > 10 ? 'Moderat' : 'Rendah'),
                'badge_color' => $wastePercentage > 20 ? 'red' : ($wastePercentage > 10 ? 'amber' : 'emerald'),
                'icon' => 'trash',
                'theme' => 'rose',
            ],
            [
                'id' => 'waste_weight',
                'title' => 'Total Timbulan Limbah',
                'value' => number_format($totalWaste, 1, ',', '.') . ' kg',
                'numeric_value' => $totalWaste,
                'subtitle' => 'Organik layak: ' . number_format($this->totalOrganicSortableWeight(), 1, ',', '.') . ' kg',
                'badge' => 'Volume Hulu',
                'badge_color' => 'amber',
                'icon' => 'scale',
                'theme' => 'amber',
            ],
            [
                'id' => 'economic_loss',
                'title' => 'Nilai Ekonomi yang Hilang',
                'value' => 'Rp ' . number_format($loss, 0, ',', '.'),
                'numeric_value' => $loss,
                'subtitle' => 'Kerugian akibat sayur terbuang',
                'badge' => 'Kerugian Finansial',
                'badge_color' => 'red',
                'icon' => 'trending-down',
                'theme' => 'red',
            ],
            [
                'id' => 'valorized_value',
                'title' => 'Nilai Ekonomi Kasgot & Kascing',
                'value' => 'Rp ' . number_format($valorizedValue, 0, ',', '.'),
                'numeric_value' => $valorizedValue,
                'subtitle' => 'Output terolah: ' . number_format($outputWeight, 1, ',', '.') . ' kg',
                'badge' => 'Value Recovery',
                'badge_color' => 'emerald',
                'icon' => 'sparkles',
                'theme' => 'emerald',
            ],
            [
                'id' => 'circularity_index',
                'title' => 'Indeks Sirkularitas Komposit',
                'value' => number_format($ciTotal, 2, ',', '.') . '%',
                'numeric_value' => $ciTotal,
                'subtitle' => 'CI Massa: ' . number_format($ciMassa, 1, ',', '.') . '% | CI Ekonomi: ' . number_format($ciEkonomi, 1, ',', '.') . '%',
                'badge' => $ciTotal >= 60 ? 'Optimal' : ($ciTotal >= 40 ? 'Cukup Baik' : 'Perlu Ditingkatkan'),
                'badge_color' => $ciTotal >= 60 ? 'blue' : ($ciTotal >= 40 ? 'cyan' : 'amber'),
                'icon' => 'refresh-cw',
                'theme' => 'blue',
            ],
        ];
    }

    /**
     * Skor Indeks Sirkularitas Lengkap
     */
    public function getCircularityScores(): array
    {
        $ciMassa = $this->materialCircularityIndex();
        $ciEkonomi = $this->economicRecoveryIndex();
        $ciTotal = $this->compositeCircularityIndex();

        return [
            'ci_massa' => [
                'score' => $ciMassa,
                'label' => 'Indeks Sirkularitas Massa (Material Recovery)',
                'formula' => '((Organik Bersih - Residu Proses) / Total Limbah) × 100%',
                'weight' => '70%',
                'color' => '#10b981', // emerald-500
            ],
            'ci_ekonomi' => [
                'score' => $ciEkonomi,
                'label' => 'Indeks Pemulihan Nilai Ekonomi (Value Recovery)',
                'formula' => '(Nilai Produk Kasgot/Kascing / Kerugian Limbah) × 100%',
                'weight' => '30%',
                'color' => '#6366f1', // indigo-500
            ],
            'ci_total' => [
                'score' => $ciTotal,
                'label' => 'Indeks Sirkularitas Komposit (Total Circularity)',
                'formula' => '(0.70 × CI_Massa) + (0.30 × CI_Ekonomi)',
                'weight' => '100%',
                'color' => '#2563eb', // blue-600
            ],
        ];
    }

    /**
     * Get a complete summary of all indicators.
     */
    public function getSummary(): array
    {
        return [
            'waste_percentage' => $this->wastePercentage(),
            'total_waste_kg' => $this->totalWasteWeight(),
            'total_supply_kg' => $this->totalSupplyWeight(),
            'total_organic_kg' => $this->totalOrganicSortableWeight(),
            'total_inorganic_kg' => $this->totalInorganicResidueWeight(),
            'waste_per_collection_point' => $this->wastePerCollectionPoint(),
            'economic_loss' => $this->economicLoss(),
            'valorized_output_weight' => $this->valorizedOutputWeight(),
            'valorized_economic_value' => $this->valorizedEconomicValue(),
            'material_circularity_index' => $this->materialCircularityIndex(),
            'economic_recovery_index' => $this->economicRecoveryIndex(),
            'composite_circularity_index' => $this->compositeCircularityIndex(),
        ];
    }
}
