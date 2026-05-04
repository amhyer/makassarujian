@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="proctorDashboard('{{ $exam->id }}')">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Monitoring Real-time: {{ $exam->title }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">Pantau aktivitas, kesehatan koneksi, dan potensi kecurangan siswa.</p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0 gap-2">
            <a href="{{ route('reporting.exam.pdf', $exam->id) }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Export PDF
            </a>
            <a href="{{ route('reporting.exam.excel', $exam->id) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Export Excel
            </a>
        </div>
    </div>

    <!-- Health Metrics Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Siswa Online</p>
            <p class="mt-2 text-3xl font-bold text-indigo-600" x-text="health.total_online">0</p>
            <p class="text-xs text-slate-400 mt-1">Aktif mengirim heartbeat</p>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Koneksi Tidak Stabil</p>
            <p class="mt-2 text-3xl font-bold text-amber-500" x-text="health.stale_connections">0</p>
            <p class="text-xs text-slate-400 mt-1">Tidak ada respon > 60 detik</p>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Total Alert Kecurangan</p>
            <p class="mt-2 text-3xl font-bold text-red-600" x-text="health.total_cheat_attempts">0</p>
            <p class="text-xs text-slate-400 mt-1">Tab switch / device change</p>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Rata-rata Keluar Fokus</p>
            <p class="mt-2 text-3xl font-bold text-slate-700" x-text="health.avg_focus_loss">0</p>
            <p class="text-xs text-slate-400 mt-1">Kali per siswa</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Live Student List -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Aktivitas Siswa</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Siswa</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Progres</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status Koneksi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Focus Loss</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="student in students" :key="student.id">
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-900" x-text="student.user.name"></div>
                                        <div class="text-xs text-slate-400" x-text="student.id.substring(0, 8)"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-indigo-600 h-1.5 rounded-full" :style="`width: ${student.progress}%`"></div>
                                            </div>
                                            <span class="text-xs font-bold text-slate-600" x-text="`${student.progress}%` text-indigo-600"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <template x-if="!student.is_stale">
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                Stabil
                                            </span>
                                        </template>
                                        <template x-if="student.is_stale">
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                                Tidak Merespon
                                            </span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold" :class="student.focus_loss_count > 3 ? 'text-red-600' : 'text-slate-600'" x-text="student.focus_loss_count"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Real-time Alerts -->
        <div>
            <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-red-50">
                    <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider">Alert Terbaru</h3>
                </div>
                <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto">
                    <template x-for="alert in alerts" :key="alert.id">
                        <div class="p-3 rounded-lg border-l-4 border-red-500 bg-red-50 animate-in fade-in slide-in-from-right-5">
                            <div class="text-xs font-bold text-red-800" x-text="alert.type_label || alert.type"></div>
                            <div class="text-sm text-red-700" x-text="alert.message"></div>
                            <div class="text-[10px] text-red-400 mt-1" x-text="new Date(alert.timestamp * 1000).toLocaleTimeString()"></div>
                        </div>
                    </template>
                    <template x-if="alerts.length === 0">
                        <div class="text-center py-10">
                            <p class="text-sm text-slate-400 italic">Belum ada kecurangan terdeteksi.</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Behavior Anomalies -->
            <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden mt-6">
                <div class="px-6 py-4 border-b border-slate-100 bg-amber-50">
                    <h3 class="text-sm font-bold text-amber-700 uppercase tracking-wider">Anomali Perilaku</h3>
                </div>
                <div class="p-6 space-y-4">
                    <template x-for="anomaly in anomalies" :key="anomaly.user + anomaly.type">
                        <div class="p-3 rounded-lg border-l-4 border-amber-500 bg-amber-50">
                            <div class="text-xs font-bold text-amber-800" x-text="anomaly.user"></div>
                            <div class="text-sm text-amber-700" x-text="anomaly.message"></div>
                        </div>
                    </template>
                    <template x-if="anomalies.length === 0">
                        <div class="text-center py-6">
                            <p class="text-sm text-slate-400 italic">Tidak ada anomali perilaku.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function proctorDashboard(examId) {
    return {
        students: [],
        alerts: [],
        anomalies: [],
        health: { total_online: 0, stale_connections: 0, total_cheat_attempts: 0, avg_focus_loss: 0 },
        
        init() {
            this.fetchData();
            setInterval(() => this.fetchData(), 5000); // Polling every 5s for real-time feel
            
            // Reverb Integration for instant events
            if (window.Echo) {
                window.Echo.private(`exam.${examId}`)
                    .listen('.AnswerUpdated', (e) => {
                        const student = this.students.find(s => s.user_id === e.user_id);
                        if (student) student.progress = e.progress;
                    })
                    .listen('.CheatDetected', (e) => {
                        this.alerts.unshift({
                            id: Date.now(),
                            type: e.type,
                            message: e.message,
                            timestamp: Math.floor(Date.now() / 1000)
                        });
                        this.health.total_cheat_attempts++;
                    });
            }
        },

        async fetchData() {
            try {
                const response = await fetch(`/api/proctor/exam/${examId}/stats`);
                const data = await response.json();
                this.students = data.live_status;
                this.alerts = data.cheat_alerts;
                this.anomalies = data.anomalies;
                this.health = data.health_metrics;
            } catch (e) {
                console.error("Failed to sync proctor data", e);
            }
        }
    }
}
</script>
@endsection
