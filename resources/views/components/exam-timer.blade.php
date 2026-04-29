<div x-data="examTimer({
    expiresAt: '{{ $expiresAt }}',
    syncUrl: '{{ route('api.exam.session') }}'
})" class="flex items-center gap-4 bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-xl border border-white/10 backdrop-blur-md">
    <!-- Timer Icon -->
    <div class="relative">
        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div x-show="isSyncing" class="absolute -top-1 -right-1">
            <div class="w-2 h-2 bg-blue-500 rounded-full animate-ping"></div>
        </div>
    </div>

    <!-- Countdown Display -->
    <div class="flex flex-col">
        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Sisa Waktu</span>
        <div class="flex items-baseline gap-1 font-mono text-2xl font-bold tabular-nums" :class="isUrgent ? 'text-red-500 animate-pulse' : 'text-white'">
            <span x-text="displayTime.h">00</span>
            <span class="text-xs text-gray-600">:</span>
            <span x-text="displayTime.m">00</span>
            <span class="text-xs text-gray-600">:</span>
            <span x-text="displayTime.s">00</span>
        </div>
    </div>
</div>

<script>
function examTimer(config) {
    return {
        expiresAt: new Date(config.expiresAt),
        remainingSeconds: 0,
        isSyncing: false,
        isUrgent: false,
        timerInterval: null,
        syncInterval: null,
        serverTimeOffset: 0, // In milliseconds

        init() {
            this.syncWithServer(); // Initial sync to get authoritative time
            
            // Start Local Countdown (per second)
            this.timerInterval = setInterval(() => {
                this.updateRemaining();
                if (this.remainingSeconds <= 0) {
                    this.handleTimeout();
                }
            }, 1000);

            // Start Server Sync (per 30 seconds)
            this.syncInterval = setInterval(() => {
                this.syncWithServer();
            }, 30000);
        },

        updateRemaining() {
            // Use authoritative corrected time: local time + offset
            const correctedNow = Date.now() + this.serverTimeOffset;
            this.remainingSeconds = Math.max(0, Math.floor((this.expiresAt.getTime() - correctedNow) / 1000));
            this.isUrgent = this.remainingSeconds < 300; // Warning color if < 5 minutes
        },

        get displayTime() {
            const h = Math.floor(this.remainingSeconds / 3600);
            const m = Math.floor((this.remainingSeconds % 3600) / 60);
            const s = this.remainingSeconds % 60;
            
            return {
                h: String(h).padStart(2, '0'),
                m: String(m).padStart(2, '0'),
                s: String(s).padStart(2, '0')
            };
        },

        async syncWithServer() {
            this.isSyncing = true;
            try {
                const startCall = Date.now();
                const res = await fetch(config.syncUrl);
                const data = await res.json();
                const endCall = Date.now();

                // --- DRIFT AUTHORITY: Synchronize with Server Time ---
                if (data.server_time) {
                    const serverTimestamp = new Date(data.server_time).getTime();
                    // Basic latency correction: assume server time was at (start + end) / 2
                    const latency = (endCall - startCall) / 2;
                    this.serverTimeOffset = serverTimestamp - (endCall - latency);
                }
                
                if (data.safe_mode !== undefined) {
                    // Emit event so the parent examEngine can catch it if needed,
                    // or we can just rely on the saveAnswer to update safe mode.
                    window.dispatchEvent(new CustomEvent('safe-mode-updated', { detail: data.safe_mode }));
                }
                
                if (data.expires_at) {
                    this.expiresAt = new Date(data.expires_at);
                    this.updateRemaining();
                }

                if (data.status === 'expired') {
                    this.handleTimeout();
                }
            } catch (e) {
                console.error('Timer sync failed');
            } finally {
                this.isSyncing = false;
            }
        },

        handleTimeout() {
            clearInterval(this.timerInterval);
            clearInterval(this.syncInterval);
            
            // Auto Submit Logic
            alert('Waktu ujian telah habis! Jawaban Anda akan dikirim otomatis.');
            document.getElementById('exam-form')?.submit();
        }
    }
}
</script>
