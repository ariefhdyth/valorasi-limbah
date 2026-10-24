<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_screen_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Valorasi Limbah & Sirkularitas');
        $response->assertSee('5 Indikator Kinerja Utama (KPI)');
        $response->assertSee('Matriks Indeks Sirkularitas');
        $response->assertSee('Timbulan Limbah & Kerugian per Titik Lokasi');
        $response->assertSee('Tren Timbulan vs Pengolahan Harian');
        $response->assertSee('Perbandingan Produksi Kasgot vs Kascing');
    }
}
