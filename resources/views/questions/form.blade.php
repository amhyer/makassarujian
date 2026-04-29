@extends('layouts.app')

@section('content')
<div class="p-8 max-w-4xl mx-auto" x-data="questionForm()">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <nav class="flex text-sm text-gray-500 mb-2">
                <a href="{{ route('questions.index') }}" class="hover:text-blue-600 transition">Bank Soal</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900 font-medium">{{ $isEdit ? 'Edit' : 'Tambah' }} Soal</span>
            </nav>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $isEdit ? 'Modifikasi Soal' : 'Buat Soal Baru' }}</h1>
        </div>
    </div>

    <form action="{{ $isEdit ? route('questions.update', $question->id) : route('questions.store') }}" method="POST" class="space-y-8">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <!-- Hidden Serialized Content -->
        <input type="hidden" name="content" :value="JSON.stringify(payload())">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Question Image -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Gambar Soal (Opsional)</label>
                    
                    <div class="flex items-center gap-6">
                        <div class="relative group w-32 h-32 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden transition-all hover:border-blue-300">
                            <template x-if="!image_url">
                                <div class="text-center">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase mt-1 block">Upload</span>
                                </div>
                            </template>
                            <template x-if="image_url">
                                <img :src="image_url" class="w-full h-full object-cover">
                            </template>
                            
                            <!-- Loading Overlay -->
                            <div x-show="is_uploading" class="absolute inset-0 bg-white/80 flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <input type="file" @change="uploadFile" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                        </div>

                        <div class="flex-1 space-y-1" x-show="image_url">
                            <p class="text-xs font-bold text-gray-500 uppercase">Gambar Berhasil Dimuat</p>
                            <button type="button" @click="removeImage" class="text-xs font-bold text-red-500 hover:text-red-700 transition">Hapus Gambar</button>
                        </div>
                        <div class="flex-1" x-show="!image_url">
                            <p class="text-xs text-gray-400 leading-relaxed italic">JPG atau PNG, Maksimal 2MB. Gambar akan muncul di atas teks soal.</p>
                        </div>
                    </div>
                </div>

                <!-- Question Editor -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Pertanyaan</label>
                    <textarea 
                        x-model="question_text"
                        rows="6" 
                        class="w-full border-gray-100 rounded-xl bg-gray-50/50 p-4 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all resize-none text-lg"
                        placeholder="Tulis soal di sini... (Dukung LaTeX dengan $...$)"
                    ></textarea>
                    @error('question_text') <p class="mt-2 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Options Builder -->
                <div x-show="type === 'mcq'" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wider">Pilihan Jawaban</label>
                        <button type="button" @click="addOption" :disabled="options.length >= 5" class="text-xs font-bold text-blue-600 hover:text-blue-800 disabled:opacity-50 transition">
                            + Tambah Opsi
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(option, index) in options" :key="index">
                            <div class="flex items-center gap-4 group transition-all">
                                <!-- Status / Radio -->
                                <button type="button" 
                                        @click="setCorrect(index)"
                                        class="w-10 h-10 rounded-xl flex items-center justify-center transition-all border-2"
                                        :class="option.is_correct ? 'bg-green-500 border-green-500 text-white shadow-lg shadow-green-100' : 'bg-gray-50 border-gray-100 text-gray-400 hover:border-blue-200'">
                                    <span class="font-bold text-sm" x-text="option.key"></span>
                                </button>

                                <!-- Input -->
                                <div class="flex-1 relative">
                                    <input type="text" 
                                           x-model="option.text"
                                           class="w-full border-gray-100 rounded-xl bg-gray-50/50 px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-sm"
                                           :placeholder="'Opsi ' + option.key">
                                    
                                    <!-- Delete button -->
                                    <button type="button" 
                                            @click="removeOption(index)" 
                                            x-show="options.length > 2"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Explanation -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Pembahasan</label>
                    <textarea name="explanation" rows="3" class="w-full border-gray-100 rounded-xl bg-gray-50/50 p-4 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all resize-none text-sm" placeholder="Tulis penjelasan jawaban jika ada...">{{ old('explanation', $question->explanation) }}</textarea>
                </div>
            </div>

            <!-- Right Column: Settings -->
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6 sticky top-8">
                    <h2 class="font-bold text-gray-900 border-b border-gray-50 pb-4">Pengaturan Soal</h2>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Mata Pelajaran</label>
                        <select name="subject_id" class="w-full border-gray-100 rounded-xl bg-gray-50 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ old('subject_id', $question->subject_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Kelas</label>
                        <select name="class_id" class="w-full border-gray-100 rounded-xl bg-gray-50 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('class_id', $question->class_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Tingkat Kesulitan</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="lvl in ['easy', 'medium', 'hard']">
                                <label class="cursor-pointer">
                                    <input type="radio" name="difficulty" :value="lvl" x-model="difficulty" class="hidden peer">
                                    <div class="text-center py-2 rounded-lg text-[10px] font-bold uppercase border border-gray-100 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition-all"
                                         x-text="lvl"></div>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-sm hover:bg-blue-700 transition shadow-xl shadow-blue-100 active:scale-95 transform">
                            {{ $isEdit ? 'Simpan Perubahan' : 'Terbitkan Soal' }}
                        </button>
                        <a href="{{ route('questions.index') }}" class="block text-center mt-4 text-xs font-bold text-gray-400 hover:text-gray-600 transition">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function questionForm() {
    return {
        type: '{{ old('type', $question->type ?? 'mcq') }}',
        difficulty: '{{ old('difficulty', $question->difficulty ?? 'medium') }}',
        question_text: @json(old('question_text', $question->question_text ?? '')),
        image_url: '{{ old('image_url', $question->content['meta']['image'] ?? '') }}',
        is_uploading: false,
        options: @json(old('options', $isEdit ? $question->options : [
            ['key' => 'A', 'text' => '', 'is_correct' => false],
            ['key' => 'B', 'text' => '', 'is_correct' => false]
        ])),

        async uploadFile(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', '{{ csrf_token() }}');

            this.is_uploading = true;
            try {
                const res = await fetch('{{ route('questions.upload') }}', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.url) {
                    this.image_url = data.url;
                }
            } catch (err) {
                alert('Gagal mengunggah gambar.');
            } finally {
                this.is_uploading = false;
            }
        },

        removeImage() {
            this.image_url = '';
        },

        addOption() {
            if (this.options.length < 5) {
                const nextKey = String.fromCharCode(65 + this.options.length);
                this.options.push({ key: nextKey, text: '', is_correct: false });
            }
        },

        removeOption(index) {
            if (this.options.length > 2) {
                this.options.splice(index, 1);
                // Re-key options
                this.options.forEach((opt, i) => {
                    opt.key = String.fromCharCode(65 + i);
                });
            }
        },

        setCorrect(index) {
            this.options.forEach((opt, i) => {
                opt.is_correct = (i === index);
            });
        },

        payload() {
            return {
                question_text: this.question_text,
                options: this.options,
                meta: { 
                    latex: true,
                    image: this.image_url 
                }
            }
        }
    }
}
</script>
@endsection
