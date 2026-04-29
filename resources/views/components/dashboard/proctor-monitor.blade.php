<div x-data="proctorDashboard('{{ $exam->id }}')" class="space-y-6">
    <!-- Stats Cards (Cached Aggregates) -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-slate-100">
            <dt class="truncate text-sm font-medium text-slate-500">Total Peserta</dt>
            <dd class="mt-1 text-3xl font-bold tracking-tight text-indigo-600" x-text="stats.total_participants">...</dd>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-slate-100">
            <dt class="truncate text-sm font-medium text-slate-500">Selesai</dt>
            <dd class="mt-1 text-3xl font-bold tracking-tight text-green-600" x-text="stats.completed">...</dd>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-slate-100">
            <dt class="truncate text-sm font-medium text-slate-500">Sedang Mengerjakan</dt>
            <dd class="mt-1 text-3xl font-bold tracking-tight text-amber-600" x-text="stats.ongoing">...</dd>
        </div>
    </div>

    <!-- Student Status Table (Real-time Patches) -->
    <div class="bg-white shadow-sm border border-slate-100 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-900">Status Peserta Live</h3>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                Terhubung ke Reverb
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Update Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="student in students" :key="student.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900" x-text="student.name"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-indigo-500 h-full transition-all duration-500" :style="`width: ${student.progress || 0}%`"></div>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500" x-text="`${student.progress || 0}%` "></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="student.online ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'" 
                                      class="inline-flex px-2 py-0.5 rounded text-xs font-bold"
                                      x-text="student.online ? 'Online' : 'Offline'">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400" x-text="student.last_update"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('proctorDashboard', (examId) => ({
        examId: examId,
        stats: { total_participants: 0, completed: 0, ongoing: 0 },
        students: [],
        
        async init() {
            // 1. Initial Load from Redis Cache
            const response = await fetch(`/api/proctor/exam/${this.examId}/stats`);
            const data = await response.json();
            this.stats = data.stats;
            
            if (data.realtime_enabled === false) {
                this.addToast(data.message, 'error');
            }

            this.students = data.live_status.map(s => ({
                id: s.user_id,
                name: s.user.name,
                progress: s.progress || 0,
                online: false,
                last_update: s.updated_at
            }));

            // 2. Subscribe to Reverb Real-time Events
            window.Echo.join(`exam.${this.examId}`)
                .here((users) => {
                    users.forEach(user => this.updateOnlineStatus(user.id, true));
                })
                .joining((user) => {
                    this.updateOnlineStatus(user.id, true);
                    this.addToast(`${user.name} bergabung`, 'success');
                })
                .leaving((user) => {
                    this.updateOnlineStatus(user.id, false);
                })
                .listen('.AnswerUpdated', (e) => {
                    this.patchStudentData(e.user_id, { 
                        progress: e.progress, 
                        last_update: e.last_update 
                    });
                })
                .listen('.BatchProgressUpdated', (e) => {
                    // Handle high-efficiency batch updates from background worker
                    e.batch.forEach(update => {
                        this.patchStudentData(update.user_id, {
                            progress: update.progress,
                            last_update: update.last_update
                        });
                    });
                })
                .listen('.StatsAggregated', (e) => {
                    // --- HIGH EFFICIENCY: Interval-based global stats update ---
                    this.stats = e.stats;
                })
                .listen('.CheatDetected', (e) => {
                    this.addToast(`Kecurangan: ${e.type} oleh ${e.student_name}`, 'error');
                });
        },

        updateOnlineStatus(userId, status) {
            const student = this.students.find(s => s.id === userId);
            if (student) student.online = status;
        },

        patchStudentData(userId, data) {
            const student = this.students.find(s => s.id === userId);
            if (student) {
                Object.assign(student, data);
            }
        },

        addToast(msg, type) {
            if (window.addToast) window.addToast(msg, type);
        }
    }));
});
</script>
