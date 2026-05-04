@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="{ openModal: false, selectedExamId: null, selectedExamTitle: '' }">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Distribusi Ujian (Assign Peserta)</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih ujian dan tentukan siswa mana saja yang dapat mengikutinya.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Table Section -->
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl p-6">
        <div class="mt-4 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Judul Ujian</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Mata Pelajaran</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Kelas</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Total Peserta</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($exams as $exam)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">{{ $exam->title }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $exam->subject->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $exam->gradeLevel->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $exam->participants_count ?? 0 }} Siswa</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                    <button 
                                        @click="openModal = true; selectedExamId = '{{ $exam->id }}'; selectedExamTitle = '{{ addslashes($exam->title) }}'"
                                        class="text-indigo-600 hover:text-indigo-900 font-semibold"
                                    >
                                        Assign Siswa
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-sm text-center text-slate-500">Belum ada ujian. Silakan buat ujian di menu Bank Soal.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Pagination -->
        <div class="mt-4">
            {{ $exams->links() }}
        </div>
    </div>

    <!-- Modal Assign Siswa -->
    <div x-show="openModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="openModal" @click.away="openModal = false" class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">
                                    Assign Peserta ke: <span x-text="selectedExamTitle" class="text-indigo-600"></span>
                                </h3>
                                <div class="mt-4 max-h-96 overflow-y-auto">
                                    <form :action="`/ujian/${selectedExamId}/peserta`" method="POST" id="assignForm">
                                        @csrf
                                        <table class="min-w-full divide-y divide-slate-200">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                                        Pilih
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                                        Nama Siswa
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                                        Email
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-slate-200">
                                                @forelse($students as $student)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                                        {{ $student->name }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                                        {{ $student->email }}
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 text-center">
                                                        Belum ada data siswa di sekolah ini.
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button 
                            type="submit" 
                            form="assignForm" 
                            @click.prevent="if(confirm('Apakah Anda yakin ingin menyimpan perubahan peserta? Siswa yang tidak dicentang akan kehilangan akses jika sudah pernah di-assign.')) document.getElementById('assignForm').submit()"
                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto"
                        >
                            Simpan Perubahan
                        </button>
                        <button type="button" @click="openModal = false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection