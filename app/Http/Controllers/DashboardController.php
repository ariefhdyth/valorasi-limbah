<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Commodity;
use App\Models\ValorizationBatch;
use App\Models\ValorizationMethod;
use App\Models\WasteLog;
use App\Services\ValorizationCalculatorService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama Dashboard Valorasi Limbah & Indeks Sirkularitas.
     */
    public function index(Request $request, ValorizationCalculatorService $calculator)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $collectionPointId = $request->filled('collection_point_id') ? (int) $request->input('collection_point_id') : null;

        // 1. Terapkan filter ke Service Kalkulator
        $calculator->setDateRange($startDate, $endDate);
        if ($collectionPointId) {
            $calculator->setCollectionPoint($collectionPointId);
        }

        // 2. Ambil 5 Card KPI Utama
        $kpiCards = $calculator->getKpiCards();

        // 3. Ambil Data Bar Chart: Timbulan Limbah & Nilai Ekonomi Hilang per Titik Lokasi
        $barChartData = $calculator->wasteAndLossPerCollectionPoint();

        // 4. Ambil Data Line Chart: Tren Timbulan vs Pengolahan Harian
        $lineChartData = $calculator->dailyWasteVsProcessingTrend();

        // 5. Ambil Data Perbandingan Produksi Kasgot vs Kascing
        $methodComparison = $calculator->methodComparison();

        // 6. Ambil Skor Indeks Sirkularitas (Massa, Ekonomi, dan Gabungan/Komposit)
        $circularityScores = $calculator->getCircularityScores();

        // 7. Ambil Komposisi Limbah per Komoditas Sayur (untuk visual Donut Chart)
        $commodityData = $calculator->wasteByCommodity();

        // 8. Ambil Master Data untuk Filter Dropdown & Ringkasan Titik Kumpul
        $collectionPoints = CollectionPoint::all();

        // 9. Ambil Transaksi Log Limbah Terbaru (dengan eager loading)
        $recentWasteLogs = WasteLog::with(['collectionPoint', 'commodity', 'recorder'])
            ->when($startDate, fn($q) => $q->where('log_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('log_date', '<=', $endDate))
            ->when($collectionPointId, fn($q) => $q->where('collection_point_id', $collectionPointId))
            ->orderByDesc('log_date')
            ->orderByDesc('id')
            ->take(7)
            ->get();

        // 10. Ambil Batch Biokonversi Terbaru
        $recentBatches = ValorizationBatch::with('valorizationMethod')
            ->when($startDate, fn($q) => $q->where('start_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('start_date', '<=', $endDate))
            ->orderByDesc('start_date')
            ->take(5)
            ->get();

        return view('dashboard.index', [
            'kpiCards' => $kpiCards,
            'barChartData' => $barChartData,
            'lineChartData' => $lineChartData,
            'methodComparison' => $methodComparison,
            'circularityScores' => $circularityScores,
            'commodityData' => $commodityData,
            'collectionPoints' => $collectionPoints,
            'recentWasteLogs' => $recentWasteLogs,
            'recentBatches' => $recentBatches,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'collection_point_id' => $collectionPointId,
            ],
            'activePointName' => $collectionPointId
                ? optional($collectionPoints->firstWhere('id', $collectionPointId))->name
                : 'Semua Titik Timbulan',
        ]);
    }
}
