@extends('layouts.app')

@section('content')
<div class="dashboard-page" x-data="dashboardAdminFkgg()" x-init="initChart()">
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title">Dashboard FKKG</h2>
            <p class="dashboard-subtitle">Ringkasan pertumbuhan tenant dan aktivitas tryout antar sekolah.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <template x-for="period in periods" :key="period">
                <button
                    type="button"
                    @click="activePeriod = period"
                    class="dashboard-filter"
                    :class="activePeriod === period && 'dashboard-filter-active'"
                    x-text="period"
                ></button>
            </template>
        </div>
    </div>

    <!-- 4 Info Boxes -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.stat-card 
            title="Sekolah Terhubung" 
            value="{{ $metrics['sekolah_terhubung'] }}" 
            subtitle="Tenant aktif terintegrasi"
            trend="+12%"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>'
            color="indigo"
        />
        
        <x-dashboard.stat-card 
            title="Total Bank Soal" 
            value="{{ $metrics['total_bank_soal'] }}" 
            subtitle="Siap dipakai untuk distribusi"
            trend="+24"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>'
            color="emerald"
        />

        <x-dashboard.stat-card 
            title="Tryout Aktif" 
            value="{{ $metrics['tryout_aktif'] }}" 
            subtitle="Sesi yang sedang berjalan"
            trend="Realtime"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>'
            color="amber"
        />

        <x-dashboard.stat-card 
            title="Distribusi Soal" 
            value="{{ $metrics['distribusi_soal'] }}" 
            subtitle="Distribusi lintas sekolah"
            trend="+9%"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>'
            color="sky"
        />
    </div>

    <!-- Charts and Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-dashboard.chart-card title="Penggunaan Tryout (Bulan Ini)" id="tryoutUsageChart" />
        </div>
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="px-4 py-3 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-800">Sekolah Berlangganan Baru</h3>
                </div>
                <div class="p-0">
                    <ul class="divide-y divide-slate-100">
                        <li class="p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                    SM
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-800 text-sm">SMA Negeri 1 Makassar</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Berlangganan Paket Platinum</p>
                                </div>
                            </div>
                        </li>
                        <li class="p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold">
                                    SM
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-800 text-sm">SMA Islam Athirah</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Berlangganan Paket Gold</p>
                                </div>
                            </div>
                        </li>
                        <li class="p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 font-bold">
                                    SM
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-800 text-sm">SMA Negeri 5 Makassar</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Berlangganan Paket Silver</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="px-4 py-3 border-t border-slate-200 text-center">
                    <a href="#" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat Semua Sekolah</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
<script>
    function dashboardAdminFkgg() {
        return {
            periods: ['Hari ini', 'Minggu ini', 'Bulan ini'],
            activePeriod: 'Bulan ini',
            initChart() {
                setTimeout(() => {
                    document.getElementById('tryoutUsageChart-skeleton').classList.add('hidden');
                    document.getElementById('tryoutUsageChart').classList.remove('hidden');

                    const options = {
                        series: [{
                            name: 'Total Akses Tryout',
                            data: [150, 230, 224, 218, 135, 147, 260]
                        }],
                        chart: {
                            type: 'bar',
                            height: 250,
                            toolbar: { show: false }
                        },
                        colors: ['#0ea5e9'],
                        plotOptions: {
                            bar: { borderRadius: 4, horizontal: false, }
                        },
                        dataLabels: { enabled: false },
                        xaxis: {
                            categories: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']
                        }
                    };

                    const chart = new ApexCharts(document.querySelector("#tryoutUsageChart"), options);
                    chart.render();
                }, 800);
            }
        }
    }
</script>
@endsection
