@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Ujian Berlangsung</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau sesi ujian yang sedang berjalan secara real-time.</p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <a href="{{ route('monitoring.ujian-berlangsung') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Refresh
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 gap-5 sm:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-green-200 p-5">
            <p class="text-sm font-medium text-green-600 truncate">Sesi Aktif Sekarang</p>
            <p class="mt-1 text-3xl font-semibold text-green-700">{{ number_format($stats['active_sessions']) }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-slate-900/5 p-5">
            <p class="text-sm font-medium text-slate-500 truncate">Total Peserta (Semua)</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ number_format($stats['total_participants']) }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-slate-900/5 p-5">
            <p class="text-sm font-medium text-slate-500 truncate">Sekolah Aktif</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ number_format($stats['active_schools']) }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg ring-1 ring-red-200 p-5">
            <p class="text-sm font-medium text-red-500 truncate">Alert Kecurangan (Hari Ini)</p>
            <p class="mt-1 text-3xl font-semibold text-red-600">{{ number_format($stats['cheat_alerts']) }}</p>
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
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Siswa</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Sekolah (Tenant)</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Ujian</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Waktu Mulai</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($sessions as $session)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">
                                            {{ strtoupper(substr($session->user->name ?? 'U', 0, 2)) }}
                                        </span>
                                        {{ $session->user->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $session->tenant->school_name ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $session->exam->title ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $session->started_at ? $session->started_at->format('d M Y, H:i') : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ ucfirst($session->status) }}
                                        </span>
                                        <a href="{{ route('monitoring.exam.proctor', $session->exam_id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold">Proctor Dashboard &rarr;</a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-sm text-slate-500">
                                    <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tidak ada ujian yang sedang berlangsung.
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
            {{ $sessions->links() }}
        </div>
    </div>
</div>
@endsection