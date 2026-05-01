@extends('layouts.app')

@section('content')
<!-- LZ-String for High Performance Compression -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lz-string/1.4.4/lz-string.min.js"></script>

<div x-data="examEngine({
    attemptId: '{{ $attempt->id }}',
    saveUrl: '{{ route('api.exam.save-answer') }}',
    initialAnswers: @json($attempt->answers ?? []),
    questions: @json($questions)
})" class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
    <!-- Sync Status Floating Alert -->
    <div x-show="offlineBuffer.length > 0" 
         x-transition
         class="fixed top-20 right-4 z-50 bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-2xl flex items-center gap-3 animate-pulse">
        <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
        <div class="text-sm text-amber-800 font-medium">
            <span x-text="offlineBuffer.length"></span> Jawaban belum tersinkronisasi. Mencoba ulang...
        </div>
    </div>

    <!-- Safe Mode Stabilization Banner -->
    <div x-show="isSafeMode" 
         x-transition
         style="display: none;"
         class="mb-6 bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-md flex items-center gap-3">
        <svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div class="text-sm text-amber-800 font-medium">
            <strong>⚠️ Sistem dalam mode stabilisasi.</strong> Jawaban Anda tetap tersimpan langsung secara aman. Fitur real-time sementara dinonaktifkan.
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Question Area -->
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg text-sm font-bold">Soal No. <span x-text="currentQuestionIndex + 1"></span></span>
                        <div class="flex items-center gap-2">
                            <template x-if="isSyncing">
                                <span class="text-xs text-slate-400 italic">Menyimpan...</span>
                            </template>
                            <template x-if="!isSyncing && lastSynced">
                                <span class="text-xs text-emerald-500 font-medium">Tersimpan</span>
                            </template>
                        </div>
                    </div>

                    <div class="prose prose-slate max-w-none mb-10">
                        <div class="text-xl leading-relaxed text-slate-800" x-html="currentQuestion.text"></div>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(option, key) in currentQuestion.options" :key="key">
                            <label class="relative flex items-center p-4 cursor-pointer rounded-xl border-2 transition-all duration-200"
                                   :class="selectedAnswer === key ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 hover:border-slate-200'">
                                <input type="radio" 
                                       :value="key" 
                                       x-model="selectedAnswer"
                                       @change="saveAnswer(currentQuestion.id, key)"
                                       class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                <span class="ml-4 text-slate-700 font-medium">
                                    <span class="text-slate-400 font-bold mr-2" x-text="key + '.'"></span>
                                    <span x-text="option"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="bg-slate-50 px-8 py-4 flex justify-between">
                    <button @click="prevQuestion" :disabled="currentQuestionIndex === 0" class="px-6 py-2 rounded-lg font-bold text-slate-600 disabled:opacity-30">Sebelumnya</button>
                    <button @click="nextQuestion" :disabled="currentQuestionIndex === questions.length - 1" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 disabled:opacity-30">Selanjutnya</button>
                </div>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <div class="space-y-6">
            <x-exam-timer expiresAt="{{ $attempt->expires_at }}" syncUrl="{{ route('api.exam.session') }}" />

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h4 class="font-bold text-slate-800 mb-4">Navigasi Soal</h4>
                <div class="grid grid-cols-5 gap-2">
                    <template x-for="(q, index) in questions" :key="q.id">
                        <button @click="currentQuestionIndex = index"
                                class="w-10 h-10 rounded-lg flex items-center justify-center font-bold transition-all"
                                :class="getQuestionNavClass(index)">
                            <span x-text="index + 1"></span>
                        </button>
                    </template>
                </div>
                
                <hr class="my-6 border-slate-100">
                
                <button @click="finishExam" :disabled="isSubmitting" class="w-full py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition-all disabled:opacity-50 flex justify-center items-center gap-2">
                    <span x-show="!isSubmitting">Selesai Ujian</span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function examEngine(config) {
    return {
        attemptId: config.attemptId,
        questions: config.questions || [],
        currentQuestionIndex: 0,
        answers: config.initialAnswers || {},
        offlineBuffer: [],
        isSyncing: false,
        lastSynced: true,
        isSafeMode: false,
        isSubmitting: false,
        db: null,

        async init() {
            await this.initDB();
            await this.loadBufferFromDB();
            
            // Background sync process for buffer (every 5 seconds)
            setInterval(() => this.processBuffer(), 5000);

            // Listen to timer sync events for safe mode updates
            window.addEventListener('safe-mode-updated', (e) => {
                this.isSafeMode = e.detail;
            });
        },

        async initDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(`exam_${this.attemptId}_db`, 1);
                request.onupgradeneeded = (e) => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains('buffer')) {
                        db.createObjectStore('buffer', { autoIncrement: true });
                    }
                };
                request.onsuccess = (e) => {
                    this.db = e.target.result;
                    resolve();
                };
                request.onerror = reject;
            });
        },

        async loadBufferFromDB() {
            if (!this.db) return;
            const transaction = this.db.transaction(['buffer'], 'readonly');
            const store = transaction.objectStore('buffer');
            const request = store.getAll();
            return new Promise((resolve) => {
                request.onsuccess = () => {
                    this.offlineBuffer = request.result || [];
                    resolve();
                };
            });
        },

        async saveAnswer(questionId, answer) {
            this.isSyncing = true;
            this.lastSynced = false;

            const payload = {
                question_id: questionId,
                selected_option: answer,
                timestamp: new Date().toISOString()
            };

            try {
                const response = await fetch(config.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                if (response.status === 200) {
                    this.lastSynced = true;
                    const data = await response.json();
                    if (data.safe_mode !== undefined) {
                        this.isSafeMode = data.safe_mode;
                    }
                } else {
                    await this.addToBuffer(payload);
                }
            } catch (e) {
                await this.addToBuffer(payload);
            } finally {
                this.isSyncing = false;
            }
        },

        async addToBuffer(payload) {
            // --- COMPRESSION: Minimize footprint ---
            const compressed = LZString.compressToUTF16(JSON.stringify(payload));
            
            if (this.db) {
                try {
                    const transaction = this.db.transaction(['buffer'], 'readwrite');
                    transaction.objectStore('buffer').add(compressed);
                    this.offlineBuffer.push(compressed);
                } catch (e) {
                    console.error("Storage full or quota exceeded", e);
                }
            }
        },

        async processBuffer() {
            if (this.offlineBuffer.length === 0 || this.isSyncing) return;

            // Decompress the first item
            const compressed = this.offlineBuffer[0];
            const item = JSON.parse(LZString.decompressFromUTF16(compressed));

            try {
                const response = await fetch(config.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(item)
                });

                if (response.status === 200) {
                    const data = await response.json();
                    if (data.safe_mode !== undefined) {
                        this.isSafeMode = data.safe_mode;
                    }
                    
                    // Success, remove from IndexedDB
                    await this.removeFromDB();
                    this.offlineBuffer.shift();
                    this.processBuffer(); // Process next
                }
            } catch (e) {
                console.warn('Sync failed');
            }
        },

        async removeFromDB() {
            if (!this.db) return;
            const transaction = this.db.transaction(['buffer'], 'readwrite');
            const store = transaction.objectStore('buffer');
            const request = store.openCursor();
            request.onsuccess = (e) => {
                const cursor = e.target.result;
                if (cursor) {
                    cursor.delete();
                }
            };
        },

        getQuestionNavClass(index) {
            const qId = this.questions[index].id;
            const isCurrent = this.currentQuestionIndex === index;
            const isAnswered = !!this.answers[qId];

            if (isCurrent) return 'bg-indigo-600 text-white ring-4 ring-indigo-100';
            if (isAnswered) return 'bg-emerald-100 text-emerald-700';
            return 'bg-slate-100 text-slate-400 hover:bg-slate-200';
        },

        nextQuestion() { if (this.currentQuestionIndex < this.questions.length - 1) this.currentQuestionIndex++; },
        prevQuestion() { if (this.currentQuestionIndex > 0) this.currentQuestionIndex--; },

        finishExam() {
            if (this.isSubmitting) return;

            if (confirm('Apakah Anda yakin ingin mengakhiri ujian? Semua jawaban akan disimpan secara permanen.')) {
                // Final sync check
                if (this.offlineBuffer.length > 0) {
                    alert('Mohon tunggu, masih ada jawaban yang sedang disinkronkan ke server...');
                    return;
                }
                
                this.isSubmitting = true; // Kunci tombol agar tidak dobel submit

                // Submit to backend
                fetch('{{ route('api.exam.submit') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ attempt_id: this.attemptId })
                }).then(async (res) => {
                    if (res.ok) {
                        window.location.href = '{{ route('siswa.dashboard') }}';
                    } else {
                        alert('Terjadi kesalahan saat menyimpan ujian. Silakan coba lagi.');
                        this.isSubmitting = false;
                    }
                }).catch(e => {
                    alert('Koneksi terputus. Pastikan internet Anda stabil untuk mengakhiri ujian.');
                    this.isSubmitting = false;
                });
            }
        }
    }
}
</script>
@endsection
