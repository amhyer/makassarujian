@extends('layouts.app')

@section('content')
<div class="dashboard-page" x-data="dashboardSiswa()" x-init="initChart()">
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title">Dashboard Siswa</h2>
            <p class="dashboard-subtitle">Lihat jadwal ujian, progres nilai, dan status belajar harian.</p>
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

    @if(session('info'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center gap-3 shadow-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-medium">{{ session('info') }}</span>
    </div>
    @endif
    @if(session('success'))
    <div class="bg-indigo-50 border border-indigo-200 text-indigo-700 px-4 py-3 rounded-lg flex items-center gap-3 shadow-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    <!-- 4 Info Boxes -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.stat-card 
            title="Ujian Tersedia" 
            value="{{ $metrics['stats']['total_available'] ?? 0 }}" 
            subtitle="Siap dikerjakan"
            trend="Aktif"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>'
            color="rose"
        />
        
        <x-dashboard.stat-card 
            title="Ujian Selesai" 
            value="{{ $metrics['stats']['completed'] ?? 0 }}" 
            subtitle="Sudah diselesaikan"
            trend="+1"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            color="emerald"
        />

        <x-dashboard.stat-card 
            title="Ujian Berjalan" 
            value="{{ $metrics['stats']['ongoing'] ?? 0 }}" 
            subtitle="Sedang berlangsung"
            trend="Realtime"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>'
            color="indigo"
        />

        <x-dashboard.stat-card 
            title="Peringkat Sekolah" 
            value="{{ $metrics['peringkat'] ?? '-' }}" 
            subtitle="Posisi terbaru Anda"
            trend="Top"
            icon='<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>'
            color="amber"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Col: Upcoming Exams Action List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Ujian Hari Ini</h3>
                    <span class="text-xs font-semibold text-slate-500" x-text="activePeriod"></span>
                </div>
                <div class="p-5 md:p-6">
                    @forelse($metrics['available_exams'] ?? [] as $exam)
                        <div class="border border-slate-200 rounded-xl p-5 hover:border-indigo-300 hover:shadow-md transition-all duration-200 mb-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="bg-rose-100 text-rose-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">{{ $exam->subject->name ?? 'Ujian' }}</span>
                                        <span class="text-slate-500 text-xs flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Waktu: {{ $exam->duration_minutes }} Menit
                                        </span>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-800">{{ $exam->title }}</h4>
                                    <p class="text-sm text-slate-500 mt-1">{{ Str::limit($exam->description, 60) }}</p>
                                </div>
                                <div class="shrink-0">
                                    <a href="{{ route('ujian.lobby', $exam->id) }}" class="inline-block w-full sm:w-auto px-5 py-2.5 bg-indigo-600 text-white text-center font-semibold rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">
                                        Lihat Ujian
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <!-- Empty State -->
                        <div class="py-10 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-slate-500 font-medium">Hore! Tidak ada jadwal ujian untuk hari ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Col: Score Chart -->
        <div>
            <x-dashboard.chart-card title="Perkembangan Nilai" id="studentScoreChart" />
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
<script>
    function dashboardSiswa() {
        return {
            periods: ['Hari ini', 'Minggu ini', 'Bulan ini'],
            activePeriod: 'Hari ini',
            initChart() {
                setTimeout(() => {
                    document.getElementById('studentScoreChart-skeleton').classList.add('hidden');
                    document.getElementById('studentScoreChart').classList.remove('hidden');

                    // Ambil riwayat skor dari data metrics yang diinject melalui Laravel Blade
                    const scoreHistory = @json($metrics['score_history'] ?? []);
                    
                    let categories = [];
                    let scores = [];

                    if (scoreHistory.length > 0) {
                        scoreHistory.forEach(history => {
                            categories.push(history.exam ? history.exam.title : 'Ujian');
                            scores.push(history.score ?? 0);
                        });
                    } else {
                        // Tampilkan dummy data jika belum ada ujian (opsional, atau biarkan kosong)
                        categories = ['Belum ada ujian'];
                        scores = [0];
                    }

                    const options = {
                        series: [{
                            name: 'Nilai Ujian',
                            data: scores
                        }],
                        chart: {
                            type: 'line',
                            height: 250,
                            toolbar: { show: false }
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        colors: ['#10b981'], // Emerald
                        markers: {
                            size: 4,
                            colors: ['#fff'],
                            strokeColors: '#10b981',
                            strokeWidth: 2,
                        },
                        dataLabels: { enabled: false },
                        xaxis: {
                            categories: categories,
                            labels: {
                                trim: true,
                                style: { fontSize: '10px' }
                            }
                        },
                        yaxis: {
                            min: 0,
                            max: 100
                        }
                    };

                    const chart = new ApexCharts(document.querySelector("#studentScoreChart"), options);
                    chart.render();
                }, 800);
            }
        }
    }
</script>
@endsection
