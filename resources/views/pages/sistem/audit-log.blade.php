@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Audit Log</h2>
            <p class="mt-1 text-sm text-slate-500">Rekam jejak aktivitas admin dan perubahan data sistem secara lengkap.</p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <a href="{{ route('sistem.audit-log') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Refresh
            </a>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Waktu</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Pengguna</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi / Event</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Target Model</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tenant / Sekolah</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm text-slate-500 font-mono">
                            {{ $log->created_at ? $log->created_at->format('d/m/y H:i:s') : 'N/A' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @if($log->user)
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                    </span>
                                    <span class="text-slate-700 font-medium">{{ $log->user->name }}</span>
                                </div>
                            @else
                                <span class="text-slate-400 italic">System</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @php
                                $eventColors = [
                                    'created'  => 'bg-green-50 text-green-700 ring-green-600/20',
                                    'updated'  => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    'deleted'  => 'bg-red-50 text-red-700 ring-red-600/20',
                                    'login'    => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                    'logout'   => 'bg-slate-50 text-slate-700 ring-slate-600/20',
                                    'impersonation' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                                ];
                                $action = $log->action ?? $log->event ?? 'log';
                                $colorClass = $eventColors[$action] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $colorClass }}">
                                {{ strtoupper($action) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            <span class="font-mono text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                {{ $log->model_type ?? $log->description ?? '—' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $log->tenant->school_name ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-400 font-mono text-xs">
                            {{ $log->ip_address ?? $log->properties['ip'] ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center text-sm text-slate-500">
                            <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="font-medium text-slate-600">Belum ada audit log.</p>
                            <p class="text-slate-400 mt-1">Aktivitas sistem akan tercatat di sini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
