@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Aktivitas Siswa</h2>
            <p class="mt-1 text-sm text-slate-500">Log aktivitas siswa selama mengerjakan ujian.</p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Ekspor
            </button>
            <button type="button" class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Tambah Data
            </button>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl p-6">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <div class="relative max-w-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" class="block w-full rounded-md border-0 py-1.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Cari data...">
                </div>
            </div>
        </div>
        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Waktu</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Siswa</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Sekolah</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Aktivitas / Pelanggaran</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                    {{ $log->timestamp ? $log->timestamp->format('d M Y, H:i:s') : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $log->attempt->user->name ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $log->attempt->tenant->school_name ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                        {{ $log->type }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    <span class="text-xs text-slate-400 font-mono">{{ $log->meta['ip'] ?? '' }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-slate-500">Belum ada log aktivitas tercatat.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Pagination -->
        <div class="mt-4 pt-4 border-t border-slate-200">
            {{ $logs->links() }}
        </div>

    </div>
</div>
@endsection