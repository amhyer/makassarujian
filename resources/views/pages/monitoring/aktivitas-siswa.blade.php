@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Aktivitas Siswa</h2>
            <p class="mt-1 text-sm text-slate-500">Log aktivitas & pelanggaran siswa selama mengerjakan ujian secara real-time.</p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <a href="{{ route('monitoring.aktivitas-siswa') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Refresh
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 gap-5 sm:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-slate-900/5 p-5">
            <p class="text-sm font-medium text-slate-500 truncate">Total Log</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-red-200 p-5">
            <p class="text-sm font-medium text-red-500 truncate">Pelanggaran Hari Ini</p>
            <p class="mt-1 text-3xl font-semibold text-red-600">{{ number_format($stats['cheats']) }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-slate-900/5 p-5">
            <p class="text-sm font-medium text-slate-500 truncate">Submit Hari Ini</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ number_format($stats['submits']) }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-slate-900/5 p-5">
            <p class="text-sm font-medium text-slate-500 truncate">Tab Beralih</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ number_format($stats['offline']) }}</p>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl p-6">
        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Waktu</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Siswa</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Sekolah</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Ujian</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Pelanggaran</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($logs as $log)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                    {{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-700 font-medium">
                                    {{ $log->attempt->user->name ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $log->attempt->tenant->school_name ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $log->attempt->exam->title ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @php
                                        $typeColors = [
                                            'tab_switch'     => 'bg-red-50 text-red-700 ring-red-600/20',
                                            'blur'           => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                                            'copy_paste'     => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                                            'devtools'       => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                            'right_click'    => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        ];
                                        $colorClass = $typeColors[$log->type] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $colorClass }}">
                                        {{ str_replace('_', ' ', ucfirst($log->type)) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-400 font-mono text-xs">
                                    {{ $log->meta['ip'] ?? '—' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-sm text-slate-500">
                                    <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Belum ada log aktivitas tercatat.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- Pagination --}}
        <div class="mt-4 pt-4 border-t border-slate-200">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection