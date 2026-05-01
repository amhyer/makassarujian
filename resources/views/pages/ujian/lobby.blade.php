@extends('layouts.app')

@section('title', 'Ruang Persiapan Ujian - ' . $exam->title)

@section('content')
<div class="max-w-4xl mx-auto py-8">
    
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-800">Ruang Persiapan Ujian</h2>
        <a href="{{ route('siswa.dashboard') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Dasbor
        </a>
    </div>

    <!-- Peringatan Error / Info -->
    @if(session('error'))
    <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-8 py-10 text-white text-center">
            <span class="bg-white/20 text-indigo-50 px-3 py-1 rounded-full text-xs font-semibold tracking-wide uppercase mb-4 inline-block">
                {{ $exam->subject->name ?? 'Ujian' }}
            </span>
            <h1 class="text-3xl font-bold mb-2">{{ $exam->title }}</h1>
            <p class="text-indigo-100 max-w-2xl mx-auto">{{ $exam->description }}</p>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Info Waktu -->
                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm text-slate-500 font-medium">Durasi Ujian</div>
                        <div class="text-lg font-bold text-slate-800">{{ $exam->duration_minutes }} Menit</div>
                    </div>
                </div>

                <!-- Info Soal -->
                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm text-slate-500 font-medium">Total Soal</div>
                        <div class="text-lg font-bold text-slate-800">
                            {{ $exam->questions()->count() }} Butir
                        </div>
                    </div>
                </div>

                <!-- Info Passing Grade -->
                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm text-slate-500 font-medium">Batas Lulus (KKM)</div>
                        <div class="text-lg font-bold text-slate-800">{{ $exam->passing_grade ?? 0 }} Point</div>
                    </div>
                </div>
            </div>

            <!-- Peraturan / Peringatan -->
            <div class="border border-amber-200 bg-amber-50 rounded-xl p-6 mb-8">
                <h3 class="font-bold text-amber-800 flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Peraturan Penting (Harap Dibaca)
                </h3>
                <ul class="list-disc list-inside text-amber-700 text-sm space-y-2">
                    <li>Waktu akan mulai berjalan saat Anda menekan tombol "Mulai Kerjakan".</li>
                    <li>Sistem ini mendukung mode <strong>Safe Exam</strong>. Aktivitas berpindah tab/layar akan terekam oleh sistem.</li>
                    <li>Pastikan koneksi internet Anda stabil, meskipun jawaban akan disimpan di dalam memori perangkat saat jaringan putus.</li>
                    <li>Jika terjadi *error* secara tiba-tiba, *refresh* halaman ini untuk melanjutkan pengerjaan (waktu tetap berjalan).</li>
                </ul>
            </div>

            <div class="text-center">
                @if($attempt && $attempt->status === 'ongoing')
                    <p class="text-emerald-600 font-semibold mb-3">Anda sedang memiliki sesi ujian yang berjalan.</p>
                    <a href="{{ route('ujian.kerjakan', $attempt->id) }}" class="inline-block px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-200 transition-all transform hover:-translate-y-0.5">
                        Lanjut Mengerjakan Ujian
                    </a>
                @else
                    <form action="{{ route('ujian.start', $exam->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-block px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all transform hover:-translate-y-0.5">
                            Mulai Kerjakan Ujian Sekarang
                        </button>
                    </form>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
