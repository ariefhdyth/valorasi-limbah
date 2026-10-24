<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Dashboard Valorasi Limbah Sayur & Indeks Sirkularitas</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased flex flex-col">

    <!-- Top Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Title -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Valorasi Limbah & Indeks Sirkularitas</h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                Laravel 12
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 hidden sm:block">Aliran Limbah Hulu ke Hilir & Valuasi Biokonversi (Kasgot BSF / Kascing Cacing)</p>
                    </div>
                </div>

                <!-- Info Badges -->
                <div class="flex items-center space-x-3">
                    <div class="hidden md:flex items-center space-x-2 px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Sistem Aktif (SQLite)</span>
                    </div>
                    <div class="text-xs text-right hidden sm:block">
                        <p class="font-semibold text-slate-700">{{ $activePointName }}</p>
                        <p class="text-slate-400">
                            {{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : '30 Hari Terakhir' }}
                            {{ $filters['end_date'] ? ' s/d ' . \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') : '' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- ====================================================================== -->
        <!-- FILTER BAR: RENTANG TANGGAL & DROPDOWN TITIK LOKASI                    -->
        <!-- ====================================================================== -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <form action="{{ route('dashboard') }}" method="GET" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
                    <!-- Dropdown Titik Lokasi -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                            📍 Titik Lokasi Sumber (Timbulan)
                        </label>
                        <select name="collection_point_id" class="w-full text-sm bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                            <option value="">Semua Titik Timbulan (Agregat)</option>
                            @foreach($collectionPoints as $cp)
                                <option value="{{ $cp->id }}" {{ $filters['collection_point_id'] == $cp->id ? 'selected' : '' }}>
                                    {{ $cp->name }} ({{ $cp->type_label }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tanggal Mulai -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                            📅 Tanggal Mulai
                        </label>
                        <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                               class="w-full text-sm bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>

                    <!-- Tanggal Selesai -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                            📅 Tanggal Selesai
                        </label>
                        <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                               class="w-full text-sm bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="flex items-center space-x-2 pt-2 sm:pt-0">
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Terapkan Filter
                    </button>
                    @if($filters['start_date'] || $filters['end_date'] || $filters['collection_point_id'])
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-xl transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- ====================================================================== -->
        <!-- 1. 5 KARTU RINGKASAN KPI DENGAN INDIKATOR WARNA                        -->
        <!-- ====================================================================== -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center">
                    <svg class="w-5 h-5 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    5 Kartu Ringkasan Indikator Kinerja Utama (KPI)
                </h2>
                <span class="text-xs text-slate-500">Service: ValorizationCalculatorService</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($kpiCards as $card)
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden flex flex-col justify-between">
                        <!-- Top Colored Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 
                            {{ $card['theme'] === 'rose' ? 'bg-rose-500' : '' }}
                            {{ $card['theme'] === 'amber' ? 'bg-amber-500' : '' }}
                            {{ $card['theme'] === 'red' ? 'bg-red-500' : '' }}
                            {{ $card['theme'] === 'emerald' ? 'bg-emerald-500' : '' }}
                            {{ $card['theme'] === 'blue' ? 'bg-blue-600' : '' }}
                        "></div>

                        <div>
                            <!-- Header Card: Label & Badge -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    {{ $card['title'] }}
                                </span>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full
                                    {{ $card['badge_color'] === 'red' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $card['badge_color'] === 'amber' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $card['badge_color'] === 'emerald' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $card['badge_color'] === 'blue' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $card['badge_color'] === 'cyan' ? 'bg-cyan-100 text-cyan-700' : '' }}
                                ">
                                    {{ $card['badge'] }}
                                </span>
                            </div>

                            <!-- Big Metric Value -->
                            <div class="text-2xl font-black tracking-tight text-slate-900 my-1">
                                {{ $card['value'] }}
                            </div>
                        </div>

                        <!-- Subtitle & Context -->
                        <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
                            <span>{{ $card['subtitle'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- ====================================================================== -->
        <!-- 2. VISUALISASI GAUGE / RADIAL BAR UNTUK INDEKS SIRKULARITAS            -->
        <!-- ====================================================================== -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-2">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Visualisasi Gauge & Matriks Indeks Sirkularitas
                    </h3>
                    <p class="text-xs text-slate-500">Kombinasi efektivitas pemulihan massa material (70%) dan pemulihan nilai ekonomi (30%)</p>
                </div>
                <div class="text-xs font-semibold px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg self-start md:self-auto border border-blue-200/60 font-mono">
                    CI_Total = (0.70 × CI_Massa) + (0.30 × CI_Ekonomi)
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Radial Bar Gauge Chart (5 Kolom) -->
                <div class="lg:col-span-5 flex flex-col items-center justify-center bg-slate-50/70 rounded-2xl p-4 border border-slate-200/60">
                    <div id="chart-circularity-radial" class="w-full min-h-[320px]"></div>
                    <div class="flex items-center justify-center space-x-6 text-xs text-slate-600 mt-2">
                        <div class="flex items-center space-x-1.5">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                            <span>CI Massa (70%)</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="w-3 h-3 rounded-full bg-indigo-500 inline-block"></span>
                            <span>CI Ekonomi (30%)</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span>
                            <span>CI Komposit</span>
                        </div>
                    </div>
                </div>

                <!-- 3 Kartu Rincian Indeks Sirkularitas (7 Kolom) -->
                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- CI Massa -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">
                                    Bobot 70%
                                </span>
                                <span class="text-[11px] text-slate-400">Massa</span>
                            </div>
                            <h4 class="font-bold text-slate-800 text-xs">CI Massa (Material)</h4>
                            <div class="text-2xl font-black text-emerald-600 my-2">
                                {{ number_format($circularityScores['ci_massa']['score'], 2, ',', '.') }}%
                            </div>
                            <p class="text-[11px] text-slate-600 leading-snug mb-3">
                                Persentase fraksi organik layak olah yang dialirkan ke biokonversi bersih dari residu proses.
                            </p>
                        </div>
                        <div>
                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $circularityScores['ci_massa']['score']) }}%"></div>
                            </div>
                            <span class="text-[9px] text-slate-400 mt-1 block font-mono leading-tight">{{ $circularityScores['ci_massa']['formula'] }}</span>
                        </div>
                    </div>

                    <!-- CI Ekonomi -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded">
                                    Bobot 30%
                                </span>
                                <span class="text-[11px] text-slate-400">Ekonomi</span>
                            </div>
                            <h4 class="font-bold text-slate-800 text-xs">CI Ekonomi (Value)</h4>
                            <div class="text-2xl font-black text-indigo-600 my-2">
                                {{ number_format($circularityScores['ci_ekonomi']['score'], 2, ',', '.') }}%
                            </div>
                            <p class="text-[11px] text-slate-600 leading-snug mb-3">
                                Rasio nilai produk hilir (Kasgot/Kascing) terhadap total kerugian finansial sayur terbuang.
                            </p>
                        </div>
                        <div>
                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $circularityScores['ci_ekonomi']['score']) }}%"></div>
                            </div>
                            <span class="text-[9px] text-slate-400 mt-1 block font-mono leading-tight">{{ $circularityScores['ci_ekonomi']['formula'] }}</span>
                        </div>
                    </div>

                    <!-- CI Total / Komposit -->
                    <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-800 bg-blue-100 px-2 py-0.5 rounded">
                                    Skor 100%
                                </span>
                                <span class="text-[11px] font-semibold text-blue-600">Total</span>
                            </div>
                            <h4 class="font-bold text-blue-900 text-xs">CI Komposit Total</h4>
                            <div class="text-2xl font-black text-blue-700 my-2">
                                {{ number_format($circularityScores['ci_total']['score'], 2, ',', '.') }}%
                            </div>
                            <p class="text-[11px] text-blue-800/80 leading-snug mb-3">
                                Efisiensi sirkularitas terpadu hulu ke hilir yang menggabungkan material & nilai ekonomi.
                            </p>
                        </div>
                        <div>
                            <div class="w-full bg-blue-200 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ min(100, $circularityScores['ci_total']['score']) }}%"></div>
                            </div>
                            <span class="text-[9px] text-blue-600 mt-1 block font-mono leading-tight">{{ $circularityScores['ci_total']['formula'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ====================================================================== -->
        <!-- 3. GRAFIK PERBANDINGAN TITIK TIMBULAN & HASIL VALORASI KASGOT/KASCING  -->
        <!-- ====================================================================== -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Grafik Perbandingan Titik Timbulan (Bar/Column Chart) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight flex items-center">
                                <svg class="w-4 h-4 text-rose-500 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Perbandingan Titik Timbulan Limbah & Kerugian
                            </h3>
                            <p class="text-xs text-slate-500">Volume timbulan (kg) vs Nilai ekonomi yang hilang (Rp)</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 rounded-lg text-slate-600">Hulu</span>
                    </div>

                    <div id="chart-point-bar" class="w-full min-h-[330px]"></div>
                </div>

                <!-- Ringkasan List Titik Lokasi -->
                <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-3 gap-2 text-center text-xs">
                    @foreach($barChartData['rows'] as $row)
                        <div class="p-2 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-[10px] text-slate-400 block truncate">{{ $row['name'] }}</span>
                            <span class="font-bold text-slate-800 text-xs">{{ number_format($row['total_waste_kg'], 0, ',', '.') }} kg</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Grafik Hasil Valorasi Kasgot vs Kascing -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight flex items-center">
                                <svg class="w-4 h-4 text-emerald-600 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                Hasil Valorasi Biokonversi (Kasgot vs Kascing)
                            </h3>
                            <p class="text-xs text-slate-500">Perbandingan bahan organik masuk vs output terolah (kg)</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg border border-indigo-200/60">Hilir</span>
                    </div>

                    <div id="chart-method-comparison" class="w-full min-h-[250px]"></div>
                </div>

                <!-- Kartu Rincian Kasgot & Kascing -->
                <div class="grid grid-cols-2 gap-3 mt-4 pt-3 border-t border-slate-100">
                    @foreach($methodComparison['items'] as $item)
                        <div class="p-3 rounded-xl border {{ $item['product_name'] === 'Kasgot' ? 'border-amber-200 bg-amber-50/40' : 'border-emerald-200 bg-emerald-50/40' }}">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-xs {{ $item['product_name'] === 'Kasgot' ? 'text-amber-800' : 'text-emerald-800' }}">
                                    {{ $item['product_name'] }}
                                </span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $item['product_name'] === 'Kasgot' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $item['batch_count'] }} Batch
                                </span>
                            </div>
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between text-slate-600">
                                    <span class="text-[11px]">Nilai Valuasi:</span>
                                    <span class="font-bold text-emerald-600">Rp {{ number_format($item['economic_value_rp'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span class="text-[11px]">Rasio Konversi:</span>
                                    <span class="font-semibold text-blue-600">{{ $item['conversion_rate_percent'] }}%</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ====================================================================== -->
        <!-- 4. GRAFIK TREN HARIAN & KOMPOSISI SAYUR TERBUANG                        -->
        <!-- ====================================================================== -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Line/Area Chart: Tren Pasokan vs Limbah Harian (2 Kolom) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight">Tren Timbulan vs Pengolahan Harian (30 Hari)</h3>
                            <p class="text-xs text-slate-500">Pasokan total masuk, timbulan limbah, fraksi organik, dan bahan biokonversi</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-200/60">Area Chart</span>
                    </div>
                    <div id="chart-daily-trend" class="w-full min-h-[320px]"></div>
                </div>
            </div>

            <!-- Donut Chart: Komposisi Sayur Terbuang (1 Kolom) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight">Komposisi Sayur Terbuang</h3>
                            <p class="text-xs text-slate-500">Proporsi volume limbah per jenis sayur</p>
                        </div>
                    </div>
                    <div id="chart-commodity-donut" class="w-full min-h-[260px]"></div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <div class="space-y-1.5">
                        @foreach($commodityData['rows'] as $comm)
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700">{{ $comm['name'] }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-slate-500">{{ number_format($comm['total_kg'], 1, ',', '.') }} kg</span>
                                    <span class="text-rose-600 font-semibold">(Rp {{ number_format($comm['total_loss'], 0, ',', '.') }})</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- ====================================================================== -->
        <!-- 5. TABEL TRANSAKSI HULU & HILIR TERBARU                                -->
        <!-- ====================================================================== -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Tabel Log Limbah Hulu -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Log Timbulan Limbah Hulu Terbaru</h3>
                        <p class="text-xs text-slate-500">Penerimaan & pemilahan di titik timbulan pasar/gudang</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] border-y border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3">Komoditas / Titik</th>
                                <th class="py-2.5 px-3 text-right">Pasokan</th>
                                <th class="py-2.5 px-3 text-right">Limbah</th>
                                <th class="py-2.5 px-3 text-right">Organik</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentWasteLogs as $log)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-2.5 px-3 text-slate-500 font-mono">{{ $log->log_date->format('d/m/Y') }}</td>
                                    <td class="py-2.5 px-3">
                                        <p class="font-semibold text-slate-800">{{ $log->commodity->name ?? '-' }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $log->collectionPoint->name ?? '-' }}</p>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-medium text-slate-600">{{ number_format($log->total_supply_kg, 1, ',', '.') }} kg</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-rose-600">{{ number_format($log->waste_weight_kg, 1, ',', '.') }} kg</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-emerald-600">{{ number_format($log->organic_sortable_kg, 1, ',', '.') }} kg</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400">Tidak ada log limbah untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Batch Valorisasi Hilir -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Batch Biokonversi Hilir Terbaru</h3>
                        <p class="text-xs text-slate-500">Siklus pengolahan Kasgot (BSF) dan Kascing (Cacing)</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] border-y border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Kode Batch</th>
                                <th class="py-2.5 px-3">Metode / Produk</th>
                                <th class="py-2.5 px-3 text-right">Input</th>
                                <th class="py-2.5 px-3 text-right">Output</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentBatches as $batch)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-2.5 px-3 font-mono font-semibold text-slate-700">{{ $batch->batch_code }}</td>
                                    <td class="py-2.5 px-3">
                                        <p class="font-semibold text-slate-800">{{ $batch->valorizationMethod->product_name ?? '-' }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $batch->valorizationMethod->name ?? '-' }}</p>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-medium text-slate-600">{{ number_format($batch->input_organic_kg, 1, ',', '.') }} kg</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-600">
                                        {{ number_format($batch->effective_output_kg, 1, ',', '.') }} kg
                                        @if($batch->status === 'processing')
                                            <span class="text-[9px] text-slate-400 font-normal block">(estimasi)</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                            {{ $batch->status === 'harvested' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                            {{ $batch->status === 'processing' ? 'bg-amber-100 text-amber-800' : '' }}
                                            {{ $batch->status === 'cancelled' ? 'bg-slate-100 text-slate-600' : '' }}
                                        ">
                                            {{ $batch->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400">Tidak ada batch untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500">
            <p class="font-semibold text-slate-700">Sistem Dashboard Valorasi Limbah Sayur & Indeks Sirkularitas</p>
            <p class="mt-1">Implementasi Arsitektur Laravel 12 • Service Pattern & Form Request • Blade + Tailwind CSS + ApexCharts</p>
        </div>
    </footer>

    <!-- ====================================================================== -->
    <!-- APEXCHARTS INITIALIZATION SCRIPT                                       -->
    <!-- ====================================================================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ─────────────────────────────────────────────────────────────
            // 1. Gauge / Radial Bar: Indeks Sirkularitas
            // ─────────────────────────────────────────────────────────────
            const radialOptions = {
                series: [
                    {{ round($circularityScores['ci_massa']['score'], 1) }},
                    {{ round($circularityScores['ci_ekonomi']['score'], 1) }},
                    {{ round($circularityScores['ci_total']['score'], 1) }}
                ],
                chart: {
                    height: 330,
                    type: 'radialBar',
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
                plotOptions: {
                    radialBar: {
                        offsetY: 0,
                        startAngle: 0,
                        endAngle: 270,
                        hollow: {
                            margin: 5,
                            size: '28%',
                            background: 'transparent',
                        },
                        track: {
                            background: '#f1f5f9',
                            strokeWidth: '100%',
                            margin: 6,
                        },
                        dataLabels: {
                            name: {
                                show: true,
                                fontSize: '12px',
                                fontWeight: 600,
                                color: '#475569',
                                offsetY: -10
                            },
                            value: {
                                show: true,
                                fontSize: '18px',
                                fontWeight: 800,
                                color: '#0f172a',
                                formatter: function (val) {
                                    return val + "%";
                                }
                            },
                            total: {
                                show: true,
                                label: 'CI Total',
                                color: '#2563eb',
                                fontSize: '13px',
                                fontWeight: 700,
                                formatter: function () {
                                    return "{{ number_format($circularityScores['ci_total']['score'], 1, ',', '.') }}%";
                                }
                            }
                        }
                    }
                },
                colors: ['#10b981', '#6366f1', '#2563eb'],
                labels: ['CI Massa', 'CI Ekonomi', 'CI Komposit'],
                legend: {
                    show: false
                }
            };
            new ApexCharts(document.querySelector("#chart-circularity-radial"), radialOptions).render();

            // ─────────────────────────────────────────────────────────────
            // 2. Bar Chart: Timbulan Limbah & Kerugian per Titik Lokasi
            // ─────────────────────────────────────────────────────────────
            const barOptions = {
                series: [
                    {
                        name: 'Timbulan Limbah (kg)',
                        data: @json($barChartData['waste_kg'])
                    },
                    {
                        name: 'Kerugian Finansial (Ratus Ribu Rp)',
                        data: @json(array_map(fn($val) => round($val / 100000, 1), $barChartData['loss_rp']))
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 320,
                    fontFamily: 'Plus Jakarta Sans, sans-serif',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '45%',
                        borderRadius: 6,
                    },
                },
                colors: ['#f43f5e', '#f59e0b'],
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: @json($barChartData['labels']),
                    labels: { style: { fontSize: '11px', colors: '#64748b' } }
                },
                yaxis: [
                    {
                        title: { text: 'Berat (kg)', style: { fontSize: '11px', color: '#f43f5e' } },
                        labels: { formatter: (val) => val.toLocaleString('id-ID') + ' kg' }
                    },
                    {
                        opposite: true,
                        title: { text: 'Kerugian (x 100.000 Rp)', style: { fontSize: '11px', color: '#f59e0b' } },
                        labels: { formatter: (val) => val.toLocaleString('id-ID') }
                    }
                ],
                legend: { position: 'top', fontSize: '12px' },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (y, { seriesIndex }) {
                            if (seriesIndex === 0) return y.toLocaleString('id-ID') + ' kg';
                            return 'Rp ' + (y * 100000).toLocaleString('id-ID');
                        }
                    }
                }
            };
            new ApexCharts(document.querySelector("#chart-point-bar"), barOptions).render();

            // ─────────────────────────────────────────────────────────────
            // 3. Bar Chart Horizontal: Perbandingan Kasgot vs Kascing
            // ─────────────────────────────────────────────────────────────
            const methodOptions = {
                series: [
                    {
                        name: 'Input Organik (kg)',
                        data: @json($methodComparison['chart']['inputs'])
                    },
                    {
                        name: 'Output Produk (kg)',
                        data: @json($methodComparison['chart']['outputs'])
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 240,
                    fontFamily: 'Plus Jakarta Sans, sans-serif',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: '50%',
                        borderRadius: 5
                    }
                },
                colors: ['#94a3b8', '#059669'],
                dataLabels: { enabled: false },
                xaxis: {
                    categories: @json($methodComparison['chart']['labels']),
                    labels: { formatter: (val) => val.toLocaleString('id-ID') + ' kg' }
                },
                legend: { position: 'top', fontSize: '11px' },
                tooltip: {
                    y: { formatter: (val) => val.toLocaleString('id-ID') + ' kg' }
                }
            };
            new ApexCharts(document.querySelector("#chart-method-comparison"), methodOptions).render();

            // ─────────────────────────────────────────────────────────────
            // 4. Line/Area Chart: Tren Pasokan, Limbah & Biokonversi
            // ─────────────────────────────────────────────────────────────
            const lineOptions = {
                series: [
                    {
                        name: 'Pasokan Sayur (kg)',
                        data: @json($lineChartData['supply_series'])
                    },
                    {
                        name: 'Timbulan Limbah (kg)',
                        data: @json($lineChartData['waste_series'])
                    },
                    {
                        name: 'Organik Bersih (kg)',
                        data: @json($lineChartData['organic_series'])
                    },
                    {
                        name: 'Bahan Olah Biokonversi (kg)',
                        data: @json($lineChartData['processed_series'])
                    }
                ],
                chart: {
                    type: 'area',
                    height: 320,
                    fontFamily: 'Plus Jakarta Sans, sans-serif',
                    toolbar: { show: false }
                },
                colors: ['#0284c7', '#f43f5e', '#10b981', '#8b5cf6'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: @json($lineChartData['categories']),
                    labels: { style: { fontSize: '10px', colors: '#64748b' } }
                },
                yaxis: {
                    title: { text: 'Bobot (kg)', style: { fontSize: '11px' } },
                    labels: { formatter: (val) => Math.round(val).toLocaleString('id-ID') + ' kg' }
                },
                legend: { position: 'top', fontSize: '11px' },
                tooltip: {
                    shared: true,
                    y: { formatter: (val) => val.toLocaleString('id-ID') + ' kg' }
                }
            };
            new ApexCharts(document.querySelector("#chart-daily-trend"), lineOptions).render();

            // ─────────────────────────────────────────────────────────────
            // 5. Donut Chart: Komposisi Sayur Terbuang
            // ─────────────────────────────────────────────────────────────
            const donutOptions = {
                series: @json($commodityData['weights']),
                labels: @json($commodityData['labels']),
                chart: {
                    type: 'donut',
                    height: 260,
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
                colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: { position: 'bottom', fontSize: '11px' },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return Math.round(val) + "%";
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString('id-ID') + ' kg';
                        }
                    }
                }
            };
            new ApexCharts(document.querySelector("#chart-commodity-donut"), donutOptions).render();
        });
    </script>
</body>
</html>
