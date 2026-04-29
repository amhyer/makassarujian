@extends('layouts.app')

@section('content')
<div class="space-y-6 pb-8" x-data="dashboardAdminSekolah()" x-init="initChart()">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-slate-800">Dashboard Admin Sekolah</h2>
        <div class="text-sm text-slate-500">Periode: {{ now()->format('F Y') }}</div>
    </div>

    <!-- 4 Info Boxes -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.stat-card 
            title="Total Siswa" 
            value="{{ $metrics['total_siswa'] }}" 
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>'
            color="indigo"
        />
        
        <x-dashboard.stat-card 
            title="Ujian Aktif" 
            value="{{ $metrics['ujian_aktif'] }}" 
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>'
            color="emerald"
        />

        <x-dashboard.stat-card 
            title="Sisa Kuota Siswa" 
            value="{{ $metrics['sisa_kuota'] }}" 
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>'
            color="amber"
        />

        <x-dashboard.stat-card 
            title="Rata-rata Nilai" 
            value="{{ $metrics['rata_nilai'] }}" 
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>'
            color="rose"
        />
    </div>

    <!-- Charts and Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-dashboard.chart-card title="Aktivitas Ujian (Bulan Ini)" id="examActivityChart" />
        </div>
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="px-4 py-3 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-800">Ujian Mendatang</h3>
                </div>
                <div class="p-0">
                    <ul class="divide-y divide-slate-100">
                        <!-- Dummy Data for now, can be populated via metrics -->
                        <li class="p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-semibold text-slate-800 text-sm">Ujian Akhir Semester - Matematika</h4>
                                    <p class="text-xs text-slate-500 mt-1">Kelas 12 IPA</p>
                                </div>
                                <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded font-medium">Besok</span>
                            </div>
                        </li>
                        <li class="p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-semibold text-slate-800 text-sm">Tryout UTBK 2026</h4>
                                    <p class="text-xs text-slate-500 mt-1">Semua Siswa Kelas 12</p>
                                </div>
                                <span class="bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded font-medium">Lusa</span>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="px-4 py-3 border-t border-slate-200 text-center">
                    <a href="#" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat Semua Jadwal</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
<script>
    function dashboardAdminSekolah() {
        return {
            initChart() {
                // Simulate lazy loading / API fetch delay
                setTimeout(() => {
                    document.getElementById('examActivityChart-skeleton').classList.add('hidden');
                    document.getElementById('examActivityChart').classList.remove('hidden');

                    const options = {
                        series: [{
                            name: 'Peserta Ujian',
                            data: [30, 40, 35, 50, 49, 60, 70, 91, 125]
                        }],
                        chart: {
                            type: 'area',
                            height: 250,
                            toolbar: { show: false }
                        },
                        colors: ['#4f46e5'],
                        stroke: { curve: 'smooth' },
                        xaxis: {
                            categories: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 5', 'Minggu 6', 'Minggu 7', 'Minggu 8', 'Minggu 9']
                        },
                        dataLabels: { enabled: false }
                    };

                    const chart = new ApexCharts(document.querySelector("#examActivityChart"), options);
                    chart.render();
                }, 800); // 800ms delay for skeleton effect
            }
        }
    }
</script>
@endsection
