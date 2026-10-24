<?php

namespace App\Console\Commands;

use App\Models\CollectionPoint;
use App\Models\Commodity;
use App\Models\ValorizationBatch;
use App\Models\ValorizationMethod;
use App\Models\WasteLog;
use App\Services\ValorizationCalculatorService;
use Illuminate\Console\Command;

class TestValorizationCalculator extends Command
{
    protected $signature = 'valorasi:test-calculator {--point= : ID Titik Pengumpulan} {--start= : Tanggal Mulai (YYYY-MM-DD)} {--end= : Tanggal Selesai (YYYY-MM-DD)}';
    protected $description = 'Uji coba dan tampilkan indikator valorasi limbah & indeks sirkularitas';

    public function handle(ValorizationCalculatorService $service): int
    {
        $pointId = $this->option('point') ? (int) $this->option('point') : null;
        $start = $this->option('start');
        $end = $this->option('end');

        if ($start || $end) {
            $service->setDateRange($start, $end);
            $this->info("📅 Filter Tanggal: " . ($start ?? 'Awal') . " s/d " . ($end ?? 'Sekarang'));
        }

        if ($pointId) {
            $service->setCollectionPoint($pointId);
            $cp = CollectionPoint::find($pointId);
            $this->info("📍 Filter Titik: " . ($cp ? $cp->name : "ID #$pointId"));
        }

        $this->newLine();
        $this->line("==================================================================");
        $this->info("📊 HASIL KALKULASI INDIKATOR VALORASI LIMBAH & SIRKULARITAS");
        $this->line("==================================================================");

        $summary = $service->getSummary();

        $this->table(
            ['Indikator', 'Nilai', 'Satuan', 'Keterangan'],
            [
                [
                    'Persentase Limbah',
                    number_format($summary['waste_percentage'], 2, ',', '.'),
                    '%',
                    'Limbah terhadap total pasokan sayur masuk',
                ],
                [
                    'Nilai Ekonomi yang Hilang',
                    'Rp ' . number_format($summary['economic_loss'], 2, ',', '.'),
                    'Rupiah',
                    'Kerugian finansial dari sayur terbuang',
                ],
                [
                    'Total Berat Output Terolah',
                    number_format($summary['valorized_output_weight'], 2, ',', '.'),
                    'kg',
                    'Hasil panen aktual / estimasi biokonversi',
                ],
                [
                    'Nilai Ekonomi Kasgot/Kascing',
                    'Rp ' . number_format($summary['valorized_economic_value'], 2, ',', '.'),
                    'Rupiah',
                    'Nilai produk bernilai tambah (biokonversi)',
                ],
                [
                    'Indeks Sirkularitas Massa (CI_Massa)',
                    number_format($summary['material_circularity_index'], 2, ',', '.'),
                    '%',
                    'Material recovery (organik bersih - residu)',
                ],
                [
                    'Indeks Pemulihan Ekonomi (CI_Ekonomi)',
                    number_format($summary['economic_recovery_index'], 2, ',', '.'),
                    '%',
                    'Rasio nilai produk terhadap kerugian limbah',
                ],
                [
                    'Indeks Sirkularitas Komposit (CI_Total)',
                    number_format($summary['composite_circularity_index'], 2, ',', '.'),
                    '%',
                    '(70% CI_Massa) + (30% CI_Ekonomi)',
                ],
            ]
        );

        $this->newLine();
        $this->info("📍 Akumulasi Timbulan Limbah per Titik:");
        $perPoint = $summary['waste_per_collection_point'];

        $pointRows = [];
        foreach ($perPoint as $row) {
            $pointRows[] = [
                $row['id'],
                $row['name'],
                $row['type'],
                number_format($row['total_waste_kg'], 2, ',', '.') . ' kg',
            ];
        }

        $this->table(['ID', 'Nama Titik', 'Tipe', 'Total Limbah (kg)'], $pointRows);

        $this->info("✅ Semua kalkulasi berhasil dieksekusi dengan aman tanpa error division by zero!");
        return Command::SUCCESS;
    }
}
