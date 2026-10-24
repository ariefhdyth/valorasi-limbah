<?php

namespace Database\Seeders;

use App\Models\CollectionPoint;
use App\Models\Commodity;
use App\Models\User;
use App\Models\ValorizationBatch;
use App\Models\ValorizationMethod;
use App\Models\WasteLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ──────────────────────────────────────────────
        // 1. User (Pencatat)
        // ──────────────────────────────────────────────
        $admin = User::factory()->create([
            'name' => 'Admin Valorasi',
            'email' => 'admin@valorasi.test',
        ]);

        $operator = User::factory()->create([
            'name' => 'Operator Lapangan',
            'email' => 'operator@valorasi.test',
        ]);

        // ──────────────────────────────────────────────
        // 2. Collection Points (3 titik lokasi)
        // ──────────────────────────────────────────────
        $collectionPoints = [
            [
                'code' => 'PSR-KRM-01',
                'name' => 'Pasar Kramat Jati',
                'type' => 'market',
                'pic_name' => 'Budi Santoso',
                'daily_capacity_kg' => 5000.00,
            ],
            [
                'code' => 'GDG-CLP-01',
                'name' => 'Gudang Sortir Cilincing',
                'type' => 'warehouse',
                'pic_name' => 'Siti Rahayu',
                'daily_capacity_kg' => 3000.00,
            ],
            [
                'code' => 'RTL-BGR-01',
                'name' => 'Supermarket FreshMart Bogor',
                'type' => 'retail',
                'pic_name' => 'Andi Wijaya',
                'daily_capacity_kg' => 1500.00,
            ],
        ];

        foreach ($collectionPoints as $cp) {
            CollectionPoint::create($cp);
        }

        // ──────────────────────────────────────────────
        // 3. Commodities (5 komoditas sayur)
        // ──────────────────────────────────────────────
        $commodities = [
            [
                'name' => 'Sawi Hijau',
                'category' => 'leafy',
                'market_price_per_kg' => 8000.00,
            ],
            [
                'name' => 'Kol/Kubis',
                'category' => 'leafy',
                'market_price_per_kg' => 7500.00,
            ],
            [
                'name' => 'Tomat',
                'category' => 'fruit_veg',
                'market_price_per_kg' => 12000.00,
            ],
            [
                'name' => 'Cabai Merah',
                'category' => 'fruit_veg',
                'market_price_per_kg' => 45000.00,
            ],
            [
                'name' => 'Wortel',
                'category' => 'root',
                'market_price_per_kg' => 10000.00,
            ],
        ];

        foreach ($commodities as $c) {
            Commodity::create($c);
        }

        // ──────────────────────────────────────────────
        // 4. Valorization Methods (2 metode)
        // ──────────────────────────────────────────────
        $bsf = ValorizationMethod::create([
            'name' => 'Biokonversi BSF (Black Soldier Fly)',
            'product_name' => 'Kasgot',
            'default_conversion_rate' => 0.2000,
            'product_price_per_kg' => 3500.00,
            'duration_days' => 21,
        ]);

        $vermi = ValorizationMethod::create([
            'name' => 'Vermikompos (Cacing Tanah)',
            'product_name' => 'Kascing',
            'default_conversion_rate' => 0.3500,
            'product_price_per_kg' => 5000.00,
            'duration_days' => 30,
        ]);

        // ──────────────────────────────────────────────
        // 5. Waste Logs — 30 hari terakhir
        // ──────────────────────────────────────────────
        $allCPs = CollectionPoint::all();
        $allCommodities = Commodity::all();
        $recorders = [$admin->id, $operator->id];
        $today = Carbon::today();

        // Typical waste percentage ranges per type
        $wasteRanges = [
            'market' => [0.15, 0.30],    // Pasar: 15-30% limbah
            'warehouse' => [0.10, 0.20], // Gudang sortir: 10-20%
            'retail' => [0.08, 0.15],    // Supermarket: 8-15%
            'farm' => [0.05, 0.12],      // Lahan: 5-12%
        ];

        // Supply volume ranges per type (kg per commodity per day)
        $supplyRanges = [
            'market' => [200, 600],
            'warehouse' => [150, 400],
            'retail' => [50, 200],
            'farm' => [100, 300],
        ];

        // Organic sortable ratio (typically 70-90% of waste is sortable organic)
        $organicRatioRange = [0.70, 0.90];

        $totalOrganicAccumulated = 0;

        for ($day = 29; $day >= 0; $day--) {
            $logDate = $today->copy()->subDays($day);

            // Skip Sunday (pasar tutup/aktivitas rendah) randomly ~30% chance
            if ($logDate->dayOfWeek === Carbon::SUNDAY && fake()->boolean(30)) {
                continue;
            }

            foreach ($allCPs as $cp) {
                // Each CP logs 2-4 random commodities per day
                $dailyCommodities = $allCommodities->random(fake()->numberBetween(2, 4));

                foreach ($dailyCommodities as $commodity) {
                    $supplyRange = $supplyRanges[$cp->type];
                    $wasteRange = $wasteRanges[$cp->type];

                    $totalSupply = fake()->randomFloat(2, $supplyRange[0], $supplyRange[1]);
                    $wastePercent = fake()->randomFloat(4, $wasteRange[0], $wasteRange[1]);
                    $wasteWeight = round($totalSupply * $wastePercent, 2);

                    // Ensure waste_weight <= total_supply
                    $wasteWeight = min($wasteWeight, $totalSupply);

                    $organicRatio = fake()->randomFloat(4, $organicRatioRange[0], $organicRatioRange[1]);
                    $organicSortable = round($wasteWeight * $organicRatio, 2);
                    $inorganicResidue = round($wasteWeight - $organicSortable, 2);

                    // Ensure sum matches exactly
                    $inorganicResidue = round($wasteWeight - $organicSortable, 2);

                    WasteLog::create([
                        'log_date' => $logDate->toDateString(),
                        'collection_point_id' => $cp->id,
                        'commodity_id' => $commodity->id,
                        'total_supply_kg' => $totalSupply,
                        'waste_weight_kg' => $wasteWeight,
                        'organic_sortable_kg' => $organicSortable,
                        'inorganic_residue_kg' => $inorganicResidue,
                        'recorded_by' => fake()->randomElement($recorders),
                        'notes' => fake()->boolean(20) ? fake()->sentence() : null,
                    ]);

                    $totalOrganicAccumulated += $organicSortable;
                }
            }
        }

        // ──────────────────────────────────────────────
        // 6. Valorization Batches — berdasarkan organik terkumpul
        // ──────────────────────────────────────────────

        // Batch BSF #1: Sudah dipanen (dimulai 25 hari lalu)
        $bsfBatch1InputKg = round($totalOrganicAccumulated * 0.15, 2);
        ValorizationBatch::create([
            'batch_code' => 'BSF-' . $today->format('Ym') . '-001',
            'valorization_method_id' => $bsf->id,
            'start_date' => $today->copy()->subDays(25)->toDateString(),
            'expected_harvest_date' => $today->copy()->subDays(25)->addDays($bsf->duration_days)->toDateString(),
            'actual_harvest_date' => $today->copy()->subDays(3)->toDateString(),
            'input_organic_kg' => $bsfBatch1InputKg,
            'estimated_output_kg' => round($bsfBatch1InputKg * $bsf->default_conversion_rate, 2),
            'actual_output_kg' => round($bsfBatch1InputKg * 0.18, 2), // sedikit di bawah estimasi
            'process_residue_kg' => round($bsfBatch1InputKg * 0.05, 2),
            'status' => 'harvested',
        ]);

        // Batch BSF #2: Masih diproses (dimulai 10 hari lalu)
        $bsfBatch2InputKg = round($totalOrganicAccumulated * 0.12, 2);
        ValorizationBatch::create([
            'batch_code' => 'BSF-' . $today->format('Ym') . '-002',
            'valorization_method_id' => $bsf->id,
            'start_date' => $today->copy()->subDays(10)->toDateString(),
            'expected_harvest_date' => $today->copy()->subDays(10)->addDays($bsf->duration_days)->toDateString(),
            'actual_harvest_date' => null,
            'input_organic_kg' => $bsfBatch2InputKg,
            'estimated_output_kg' => round($bsfBatch2InputKg * $bsf->default_conversion_rate, 2),
            'actual_output_kg' => null,
            'process_residue_kg' => round($bsfBatch2InputKg * 0.03, 2),
            'status' => 'processing',
        ]);

        // Batch Kascing #1: Sudah dipanen (dimulai 35 hari lalu)
        $vermiBatch1InputKg = round($totalOrganicAccumulated * 0.10, 2);
        ValorizationBatch::create([
            'batch_code' => 'VRM-' . $today->format('Ym') . '-001',
            'valorization_method_id' => $vermi->id,
            'start_date' => $today->copy()->subDays(35)->toDateString(),
            'expected_harvest_date' => $today->copy()->subDays(35)->addDays($vermi->duration_days)->toDateString(),
            'actual_harvest_date' => $today->copy()->subDays(4)->toDateString(),
            'input_organic_kg' => $vermiBatch1InputKg,
            'estimated_output_kg' => round($vermiBatch1InputKg * $vermi->default_conversion_rate, 2),
            'actual_output_kg' => round($vermiBatch1InputKg * 0.32, 2), // sedikit di bawah estimasi
            'process_residue_kg' => round($vermiBatch1InputKg * 0.04, 2),
            'status' => 'harvested',
        ]);

        // Batch Kascing #2: Masih diproses (dimulai 12 hari lalu)
        $vermiBatch2InputKg = round($totalOrganicAccumulated * 0.08, 2);
        ValorizationBatch::create([
            'batch_code' => 'VRM-' . $today->format('Ym') . '-002',
            'valorization_method_id' => $vermi->id,
            'start_date' => $today->copy()->subDays(12)->toDateString(),
            'expected_harvest_date' => $today->copy()->subDays(12)->addDays($vermi->duration_days)->toDateString(),
            'actual_harvest_date' => null,
            'input_organic_kg' => $vermiBatch2InputKg,
            'estimated_output_kg' => round($vermiBatch2InputKg * $vermi->default_conversion_rate, 2),
            'actual_output_kg' => null,
            'process_residue_kg' => round($vermiBatch2InputKg * 0.02, 2),
            'status' => 'processing',
        ]);

        // Batch BSF #3: Dibatalkan (contoh data cancelled)
        $bsfBatch3InputKg = round($totalOrganicAccumulated * 0.05, 2);
        ValorizationBatch::create([
            'batch_code' => 'BSF-' . $today->format('Ym') . '-003',
            'valorization_method_id' => $bsf->id,
            'start_date' => $today->copy()->subDays(20)->toDateString(),
            'expected_harvest_date' => $today->copy()->subDays(20)->addDays($bsf->duration_days)->toDateString(),
            'actual_harvest_date' => null,
            'input_organic_kg' => $bsfBatch3InputKg,
            'estimated_output_kg' => round($bsfBatch3InputKg * $bsf->default_conversion_rate, 2),
            'actual_output_kg' => null,
            'process_residue_kg' => 0,
            'status' => 'cancelled',
        ]);

        $this->command->info('✅ Seeder berhasil! Data realistis telah dimuat.');
        $this->command->info("   → {$allCPs->count()} titik lokasi");
        $this->command->info("   → {$allCommodities->count()} komoditas");
        $this->command->info('   → 2 metode valorisasi (BSF & Vermikompos)');
        $this->command->info('   → ' . WasteLog::count() . ' data log limbah (30 hari)');
        $this->command->info('   → ' . ValorizationBatch::count() . ' batch valorisasi');
    }
}
