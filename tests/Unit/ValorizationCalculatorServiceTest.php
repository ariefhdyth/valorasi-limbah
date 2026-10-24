<?php

namespace Tests\Unit;

use App\Models\CollectionPoint;
use App\Models\Commodity;
use App\Models\ValorizationBatch;
use App\Models\ValorizationMethod;
use App\Models\WasteLog;
use App\Services\ValorizationCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValorizationCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_by_zero_protection_on_empty_database(): void
    {
        $service = new ValorizationCalculatorService();

        $this->assertSame(0.0, $service->wastePercentage());
        $this->assertSame(0.0, $service->economicLoss());
        $this->assertSame(0.0, $service->valorizedOutputWeight());
        $this->assertSame(0.0, $service->valorizedEconomicValue());
        $this->assertSame(0.0, $service->materialCircularityIndex());
        $this->assertSame(0.0, $service->economicRecoveryIndex());
        $this->assertSame(0.0, $service->compositeCircularityIndex());
        $this->assertIsArray($service->wastePerCollectionPoint());
        $this->assertEmpty($service->wastePerCollectionPoint());
    }

    public function test_accurate_calculations_with_known_dataset(): void
    {
        // 1. Setup master data
        $cp = CollectionPoint::create([
            'code' => 'CP-TEST-01',
            'name' => 'Pasar Induk Test',
            'type' => 'market',
            'pic_name' => 'Pak Test',
            'daily_capacity_kg' => 1000.00,
        ]);

        $commodity = Commodity::create([
            'name' => 'Sawi Putih',
            'category' => 'leafy',
            'market_price_per_kg' => 10000.00, // Rp 10.000 / kg
        ]);

        $method = ValorizationMethod::create([
            'name' => 'Biokonversi BSF',
            'product_name' => 'Kasgot',
            'default_conversion_rate' => 0.2000, // 20%
            'product_price_per_kg' => 4000.00, // Rp 4.000 / kg
            'duration_days' => 20,
        ]);

        // 2. Setup Waste Log:
        // Total Supply: 1000 kg
        // Waste Weight: 200 kg (20%)
        // Organic Sortable: 160 kg (80% of waste)
        // Inorganic Residue: 40 kg (20% of waste)
        WasteLog::create([
            'log_date' => '2026-03-01',
            'collection_point_id' => $cp->id,
            'commodity_id' => $commodity->id,
            'total_supply_kg' => 1000.00,
            'waste_weight_kg' => 200.00,
            'organic_sortable_kg' => 160.00,
            'inorganic_residue_kg' => 40.00,
        ]);

        // 3. Setup Valorization Batch:
        // Input Organic: 160 kg
        // Harvested: actual_output_kg = 32 kg (20% conversion)
        // Process residue: 10 kg
        ValorizationBatch::create([
            'batch_code' => 'BATCH-TEST-001',
            'valorization_method_id' => $method->id,
            'start_date' => '2026-03-01',
            'expected_harvest_date' => '2026-03-21',
            'actual_harvest_date' => '2026-03-21',
            'input_organic_kg' => 160.00,
            'estimated_output_kg' => 32.00,
            'actual_output_kg' => 32.00,
            'process_residue_kg' => 10.00,
            'status' => 'harvested',
        ]);

        $service = new ValorizationCalculatorService();
        $service->setDateRange('2026-03-01', '2026-03-31');

        // 1. Waste Percentage = (200 / 1000) * 100 = 20.00%
        $this->assertEquals(20.0, $service->wastePercentage());

        // 2. Economic Loss = 200 kg * Rp 10.000 = Rp 2.000.000
        $this->assertEquals(2000000.0, $service->economicLoss());

        // 3. Valorized Output Weight = 32.00 kg
        $this->assertEquals(32.0, $service->valorizedOutputWeight());

        // 4. Valorized Economic Value = 32 kg * Rp 4.000 = Rp 128.000
        $this->assertEquals(128000.0, $service->valorizedEconomicValue());

        // 5. CI_Massa = ((160 - 10) / 200) * 100 = (150 / 200) * 100 = 75.00%
        $this->assertEquals(75.0, $service->materialCircularityIndex());

        // 6. CI_Ekonomi = (128.000 / 2.000.000) * 100 = 6.40%
        $this->assertEquals(6.4, $service->economicRecoveryIndex());

        // 7. CI_Total = (0.70 * 75.00) + (0.30 * 6.40) = 52.5 + 1.92 = 54.42%
        $this->assertEquals(54.42, $service->compositeCircularityIndex());
    }

    public function test_processing_batch_falls_back_to_estimated_output(): void
    {
        $method = ValorizationMethod::create([
            'name' => 'Biokonversi BSF',
            'product_name' => 'Kasgot',
            'default_conversion_rate' => 0.2500,
            'product_price_per_kg' => 5000.00,
            'duration_days' => 20,
        ]);

        ValorizationBatch::create([
            'batch_code' => 'BATCH-PROC-001',
            'valorization_method_id' => $method->id,
            'start_date' => '2026-03-10',
            'expected_harvest_date' => '2026-03-30',
            'actual_harvest_date' => null,
            'input_organic_kg' => 200.00,
            'estimated_output_kg' => 50.00, // 200 * 0.25
            'actual_output_kg' => null,
            'process_residue_kg' => 5.00,
            'status' => 'processing',
        ]);

        $service = new ValorizationCalculatorService();
        $this->assertEquals(50.0, $service->valorizedOutputWeight());
        $this->assertEquals(250000.0, $service->valorizedEconomicValue()); // 50 * 5000
    }

    public function test_collection_point_filtering(): void
    {
        $cp1 = CollectionPoint::create([
            'code' => 'CP-01',
            'name' => 'Pasar A',
            'type' => 'market',
            'pic_name' => 'Andi',
            'daily_capacity_kg' => 500.00,
        ]);

        $cp2 = CollectionPoint::create([
            'code' => 'CP-02',
            'name' => 'Gudang B',
            'type' => 'warehouse',
            'pic_name' => 'Budi',
            'daily_capacity_kg' => 500.00,
        ]);

        $commodity = Commodity::create([
            'name' => 'Tomat',
            'category' => 'fruit_veg',
            'market_price_per_kg' => 15000.00,
        ]);

        // CP1: 100 supply, 30 waste => 30%
        WasteLog::create([
            'log_date' => '2026-03-01',
            'collection_point_id' => $cp1->id,
            'commodity_id' => $commodity->id,
            'total_supply_kg' => 100.00,
            'waste_weight_kg' => 30.00,
            'organic_sortable_kg' => 25.00,
            'inorganic_residue_kg' => 5.00,
        ]);

        // CP2: 200 supply, 20 waste => 10%
        WasteLog::create([
            'log_date' => '2026-03-01',
            'collection_point_id' => $cp2->id,
            'commodity_id' => $commodity->id,
            'total_supply_kg' => 200.00,
            'waste_weight_kg' => 20.00,
            'organic_sortable_kg' => 18.00,
            'inorganic_residue_kg' => 2.00,
        ]);

        $service = new ValorizationCalculatorService();

        // All points combined: (30 + 20) / (100 + 200) = 50 / 300 = 16.67%
        $this->assertEquals(16.67, $service->wastePercentage());

        // Filter CP1 only: 30 / 100 = 30.00%
        $service->setCollectionPoint($cp1->id);
        $this->assertEquals(30.0, $service->wastePercentage());

        // Filter CP2 only: 20 / 200 = 10.00%
        $service->setCollectionPoint($cp2->id);
        $this->assertEquals(10.0, $service->wastePercentage());
    }
}
