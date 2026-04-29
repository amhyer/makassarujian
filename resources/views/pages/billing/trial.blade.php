@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Manajemen Trial</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau dan kelola tenant yang sedang dalam masa percobaan.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300">
                <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Tenant</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Email Admin</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Mulai Trial</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Berakhir</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Sisa Hari</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($tenants as $tenant)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ $tenant->name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $tenant->admin_email }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $tenant->activated_at ? $tenant->activated_at->format('d M Y') : '-' }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $tenant->trial_ends_at ? $tenant->trial_ends_at->format('d M Y') : '-' }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @php
                                $days = $tenant->trial_ends_at ? now()->diffInDays($tenant->trial_ends_at, false) : 0;
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                                {{ $days > 3 ? 'bg-green-50 text-green-700 ring-green-600/20' : ($days > 0 ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20' : 'bg-red-50 text-red-700 ring-red-600/20') }} ring-1 ring-inset">
                                {{ $days > 0 ? "$days Hari lagi" : "Expired" }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 flex justify-end gap-2">
                            <form action="{{ route('billing.trials.extend', $tenant) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900">Perpanjang</button>
                            </form>
                            <span class="text-slate-300">|</span>
                            <form action="{{ route('billing.trials.convert', $tenant) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-900">Aktivasi</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-sm text-slate-500 italic">Belum ada tenant dalam masa trial.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $tenants->links() }}
        </div>
    </div>
</div>
@endsection