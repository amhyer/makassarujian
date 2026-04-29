@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

<div class="p-8 max-w-7xl mx-auto" x-data="{ 
    showPreview: false, 
    activeQuestion: { text: '', image: '', options: [], explanation: '', subject: '', class: '', difficulty: '' },
    
    renderLatex() {
        this.$nextTick(() => {
            document.querySelectorAll('.latex-content').forEach(el => {
                renderMathInElement(el, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: true}
                    ],
                    throwOnError : false
                });
            });
        });
    },

    preview(q) {
        this.activeQuestion = {
            text: q.content.question_text,
            image: q.content.meta?.image || '',
            options: q.content.options || [],
            explanation: q.explanation || 'Tidak ada penjelasan.',
            subject: q.subject ? q.subject.name : 'N/A',
            class: q.class ? q.class.name : 'N/A',
            difficulty: q.difficulty
        };
        this.showPreview = true;
        this.renderLatex();
    }
}">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Bank Soal</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola daftar soal ujian untuk sekolah Anda.</p>
        </div>
        <a href="{{ route('questions.create') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100 group">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
            Tambah Soal
        </a>
    </div>

    <!-- Stats / Overview (Optional but premium) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Soal</p>
                <p class="text-xl font-black text-gray-900">{{ $questions->total() }}</p>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('questions.index') }}" method="GET" class="flex flex-wrap md:flex-nowrap items-center gap-2">
            <div class="flex-1 relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       x-on:input.debounce.500ms="$el.closest('form').submit()"
                       placeholder="Cari soal..." 
                       class="w-full pl-12 pr-4 py-3 bg-transparent border-none focus:ring-0 text-sm text-gray-700"
                >
            </div>
            
            <div class="h-8 w-px bg-gray-100 hidden md:block"></div>

            <div class="w-full md:w-48">
                <select name="subject_id" 
                        onchange="this.form.submit()"
                        class="w-full border-none bg-transparent focus:ring-0 text-sm text-gray-600 font-medium cursor-pointer"
                >
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>

            <a href="{{ route('questions.index') }}" class="px-4 py-2 text-gray-400 hover:text-gray-600 transition" title="Reset Filter">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </a>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-100 rounded-2xl flex items-center gap-3 animate-fade-in">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Table Container -->
    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Konten Soal</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Mapel</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Tingkat</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Dibuat Oleh</th>
                        <th class="px-6 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($questions as $q)
                        <tr class="hover:bg-blue-50/30 transition-colors group">
                            <td class="px-6 py-6">
                                <div class="max-w-md">
                                    <div class="text-sm font-semibold text-gray-800 line-clamp-2 leading-relaxed">
                                        {!! strip_tags($q->question_text) !!}
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md bg-gray-100 text-[10px] font-bold text-gray-500 uppercase">{{ $q->type }}</span>
                                        <span class="px-2 py-0.5 rounded-md bg-gray-100 text-[10px] font-bold text-gray-500 uppercase">Kelas {{ $q->class->name ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <span class="text-sm font-bold text-gray-600">{{ $q->subject->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-6">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ 
                                    $q->difficulty == 'easy' ? 'bg-green-100 text-green-700' : 
                                    ($q->difficulty == 'hard' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') 
                                }}">
                                    <span class="w-1.5 h-1.5 rounded-full mr-2 {{ 
                                        $q->difficulty == 'easy' ? 'bg-green-500' : 
                                        ($q->difficulty == 'hard' ? 'bg-red-500' : 'bg-yellow-500') 
                                    }}"></span>
                                    {{ ucfirst($q->difficulty) }}
                                </span>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-[10px] font-black text-blue-600 uppercase">
                                        {{ substr($q->creator->name ?? 'U', 0, 1) }}
                                    </div>
                                    <span class="text-xs font-medium text-gray-700">{{ $q->creator->name ?? 'Unknown' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-right">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="preview({{ $q->toJson() }})" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Pratinjau">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    <a href="{{ route('questions.edit', $q->id) }}" class="p-2 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>
                                    <form action="{{ route('questions.destroy', $q->id) }}" method="POST" onsubmit="return confirm('Hapus soal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <p class="text-gray-400 font-medium">Tidak ada soal yang ditemukan.</p>
                                    <a href="{{ route('questions.create') }}" class="mt-4 text-blue-600 font-bold hover:underline">Buat Soal Pertama</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $questions->appends(request()->query())->links() }}
    </div>

    <!-- Modal Preview -->
    <div x-show="showPreview" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         style="display: none;">
        
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col" @click.away="showPreview = false">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white">
                <div>
                    <h3 class="font-black text-xl text-gray-900 tracking-tight">Pratinjau Soal</h3>
                    <div class="flex gap-2 mt-2">
                        <span class="text-[10px] bg-blue-600 text-white px-2.5 py-1 rounded-lg font-black uppercase tracking-wider" x-text="activeQuestion.subject"></span>
                        <span class="text-[10px] bg-gray-100 text-gray-500 px-2.5 py-1 rounded-lg font-black uppercase tracking-wider" x-text="'Kelas ' + activeQuestion.class"></span>
                    </div>
                </div>
                <button @click="showPreview = false" class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-10 overflow-y-auto space-y-10 flex-1">
                <template x-if="activeQuestion.image">
                    <div class="rounded-3xl overflow-hidden border border-gray-100 shadow-lg">
                        <img :src="activeQuestion.image" class="w-full h-auto object-contain max-h-96">
                    </div>
                </template>

                <div>
                    <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em] block mb-4">Pertanyaan</span>
                    <div class="prose prose-blue max-w-none text-gray-800 text-xl font-medium leading-relaxed latex-content" x-html="activeQuestion.text"></div>
                </div>

                <div class="space-y-4">
                    <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em] block mb-4">Pilihan Jawaban</span>
                    <template x-for="(opt, index) in activeQuestion.options" :key="index">
                        <div class="flex items-center gap-5 p-5 rounded-2xl border-2 transition-all"
                             :class="opt.is_correct ? 'border-green-500 bg-green-50/30' : 'border-gray-50 bg-white'">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm"
                                 :class="opt.is_correct ? 'bg-green-500 text-white shadow-xl shadow-green-200' : 'bg-gray-100 text-gray-400'"
                                 x-text="opt.key"></div>
                            <div class="flex-1 text-gray-700 font-bold latex-content" x-text="opt.text"></div>
                            <template x-if="opt.is_correct">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div x-show="activeQuestion.explanation" class="p-8 bg-blue-50 rounded-3xl border border-blue-100">
                    <span class="text-[10px] font-black text-blue-400 uppercase tracking-[0.3em] block mb-4">Penjelasan</span>
                    <p class="text-blue-900 text-sm font-medium leading-relaxed italic" x-text="activeQuestion.explanation"></p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex justify-end">
                <button @click="showPreview = false" class="px-8 py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition shadow-xl shadow-gray-200">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
