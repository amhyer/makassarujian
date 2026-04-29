@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="aktivasiPage()">

    {{-- ── Flash Messages ─────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 flex items-start gap-3">
        <svg class="h-5 w-5 text-green-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm font-medium text-green-800">{!! session('success') !!}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-start gap-3">
        <svg class="h-5 w-5 text-red-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
    @endif

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Aktivasi &amp; Status Tenant</h1>
            <p class="mt-1 text-sm text-slate-500">Control panel siklus hidup dan monetisasi seluruh tenant di platform.</p>
        </div>
    </div>

    {{-- ── Urgency Banner ───────────────────────────────────────────────── --}}
    @if($metrics['expiring_soon'] > 0)
    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 flex items-start gap-3">
        <svg class="h-5 w-5 text-amber-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        <div>
            <p class="text-sm font-semibold text-amber-800">⚠️ {{ $metrics['expiring_soon'] }} tenant akan expired dalam 3 hari ke depan!</p>
            <p class="text-xs text-amber-600 mt-0.5">Segera tindak lanjuti untuk mencegah kehilangan pendapatan.</p>
        </div>
    </div>
    @endif

    {{-- ── Summary Cards ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</span>
            <span class="text-3xl font-bold text-slate-900">{{ $metrics['total'] }}</span>
            <span class="text-xs text-slate-400">Semua tenant</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-yellow-600 uppercase tracking-wide">Pending</span>
            <span class="text-3xl font-bold text-yellow-500">{{ $metrics['pending'] }}</span>
            <span class="text-xs text-slate-400">Menunggu aktivasi</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">Trial</span>
            <span class="text-3xl font-bold text-blue-600">{{ $metrics['trial'] }}</span>
            <span class="text-xs text-slate-400">Masa percobaan</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-green-600 uppercase tracking-wide">Aktif</span>
            <span class="text-3xl font-bold text-green-600">{{ $metrics['active'] }}</span>
            <span class="text-xs text-slate-400">Berlangganan aktif</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-red-500 uppercase tracking-wide">Expired</span>
            <span class="text-3xl font-bold text-red-500">{{ $metrics['expired'] }}</span>
            <span class="text-xs text-slate-400">Perlu pembayaran</span>
        </div>
    </div>

    {{-- ── Tabel Lifecycle ──────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5">
        <div class="border-b border-slate-100 px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">Daftar Semua Tenant</h2>
                <p class="text-xs text-slate-400 mt-0.5">Klik baris atau tombol "▶ Aksi" untuk memperluas opsi tindakan.</p>
            </div>
            <div class="sm:ml-auto flex items-center gap-2">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
                    <input x-model="search" type="text" placeholder="Cari tenant..." class="w-48 rounded-lg border-0 bg-slate-50 py-2 pl-9 pr-3 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
                </div>
                <select x-model="filterStatus" class="rounded-lg border-0 bg-slate-50 py-2 pl-3 pr-8 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="trial">Trial</option>
                    <option value="active">Aktif</option>
                    <option value="expired">Expired</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto overflow-y-visible">
            <table class="min-w-full">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Institusi</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Tipe</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Trial Berakhir</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Sisa Hari</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Diaktifkan</th>
                        <th class="relative py-3 pl-3 pr-5"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                @forelse($tenants as $tenant)
                @php
                    $isExpired      = $tenant->status === \App\Enums\TenantStatus::Expired;
                    $daysRemaining  = $tenant->daysRemaining();
                    $isExpiringSoon = $tenant->isExpiringSoon(3);
                    $tenantActions  = $actions[$tenant->id] ?? [];
                @endphp
                <tbody x-data="{ open: false, days: 7, submitting: false }">
                    {{-- Baris utama --}}
                    <tr
                        @click="open = !open"
                        class="group border-b border-slate-100 cursor-pointer transition-colors duration-150 hover:bg-slate-50/70
                            {{ $isExpired ? 'bg-red-50/40' : ($isExpiringSoon ? 'bg-amber-50/30' : 'bg-white') }}"
                        x-show="
                            (search === '' || '{{ strtolower($tenant->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($tenant->domain ?? '') }}'.includes(search.toLowerCase())) &&
                            (filterStatus === '' || filterStatus === '{{ $tenant->status->value }}')
                        "
                    >
                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg {{ $tenant->type === 'fkkg' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700' }} flex items-center justify-center font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $tenant->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $tenant->domain ?? 'Tanpa domain' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <span class="inline-flex items-center rounded-md {{ $tenant->type === 'fkkg' ? 'bg-purple-50 text-purple-700 ring-purple-600/20' : 'bg-indigo-50 text-indigo-700 ring-indigo-600/20' }} px-2 py-1 text-xs font-medium ring-1 ring-inset">
                                {{ strtoupper($tenant->type) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $tenant->status->badgeClass() }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $tenant->status->dotColor() }}"></span>
                                {{ $tenant->status->label() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $tenant->trial_ends_at ? $tenant->trial_ends_at->format('d M Y') : '—' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold">
                            @if($daysRemaining !== null)
                                @if($daysRemaining > 3)
                                    <span class="text-slate-600">{{ $daysRemaining }} hari</span>
                                @elseif($daysRemaining > 0)
                                    <span class="text-amber-600">⚠ {{ $daysRemaining }} hari</span>
                                @elseif($daysRemaining === 0)
                                    <span class="text-red-600 animate-pulse">Hari ini!</span>
                                @else
                                    <span class="text-red-600">{{ abs($daysRemaining) }}h lalu</span>
                                @endif
                            @else
                                <span class="text-slate-400 font-normal">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $tenant->activated_at ? $tenant->activated_at->format('d M Y') : '—' }}
                        </td>
                        <td class="whitespace-nowrap py-4 pl-3 pr-5 text-right" @click.stop>
                            <button @click="open = !open"
                                :class="open ? 'bg-indigo-50 text-indigo-600' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all duration-200">
                                <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                </svg>
                                <span x-text="open ? 'Tutup' : 'Aksi'"></span>
                            </button>
                        </td>
                    </tr>

                    {{-- Baris Aksi Inline (expand) --}}
                    <tr x-show="open && (
                            search === '' || '{{ strtolower($tenant->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($tenant->domain ?? '') }}'.includes(search.toLowerCase())
                        ) && (filterStatus === '' || filterStatus === '{{ $tenant->status->value }}')"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="border-b border-slate-100 bg-slate-50/50"
                        style="display:none;"
                    >
                        <td colspan="7" class="px-5 pb-4 pt-1">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">

                                {{-- Header panel --}}
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Aksi tersedia untuk <span class="text-indigo-600">{{ $tenant->name }}</span></p>
                                    @if($tenant->status === \App\Enums\TenantStatus::Trial && $tenant->trial_ends_at)
                                    <div class="flex items-center gap-1.5 rounded-lg bg-blue-50 border border-blue-200 px-3 py-1.5">
                                        <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs font-medium text-blue-700">Trial berakhir: {{ $tenant->trial_ends_at->format('d M Y') }}</span>
                                    </div>
                                    @endif
                                </div>

                                @if(count($tenantActions) > 0)
                                <div class="flex flex-wrap items-start gap-3">

                                    @foreach($tenantActions as $action)

                                    {{-- Extend Trial — tampilkan input days --}}
                                    @if($action['key'] === 'extend-trial')
                                    <div class="flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5" @click.stop>
                                        <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <form action="{{ route('tenants.extend-trial', $tenant->id) }}" method="POST"
                                            class="flex items-center gap-2"
                                            @submit.prevent="if(days < 1 || days > 365){ alert('Hari harus antara 1 - 365'); return; } submitting=true; $el.submit()">
                                            @csrf
                                            <label class="text-xs font-medium text-blue-700 whitespace-nowrap">Perpanjang Trial</label>
                                            <input type="number" name="days" x-model="days" min="1" max="365" required
                                                class="w-16 rounded-lg border-0 bg-white py-1.5 px-2 text-sm text-center ring-1 ring-inset ring-blue-300 focus:ring-2 focus:ring-blue-500">
                                            <span class="text-xs text-blue-600">hari</span>
                                            <button type="submit" :disabled="submitting"
                                                class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 transition-colors disabled:opacity-60">
                                                <span x-show="!submitting">OK</span>
                                                <span x-show="submitting">...</span>
                                            </button>
                                        </form>
                                    </div>

                                    {{-- Activate / start-trial / suspend / expire / send-reminder --}}
                                    @else
                                    <form action="{{ route('tenants.' . $action['key'], $tenant->id) }}" method="POST"
                                        @click.stop
                                        @submit.prevent="
                                            @if(in_array($action['key'], ['suspend', 'expire']))
                                            if(confirm('Yakin ingin melakukan aksi \'{{ $action['label'] }}\' pada {{ addslashes($tenant->name) }}?\nTindakan ini akan mengubah status tenant secara permanen.')) {
                                                submitting = true; $el.submit();
                                            }
                                            @elseif($action['key'] === 'activate' && '{{ $tenant->status->value }}' === 'pending')
                                            if(confirm('Aktivasi langsung \'{{ addslashes($tenant->name) }}\' tanpa melalui trial?\nTenant akan langsung berstatus Aktif selama 1 tahun.')) {
                                                submitting = true; $el.submit();
                                            }
                                            @else
                                            submitting = true; $el.submit();
                                            @endif
                                        ">
                                        @csrf

                                        {{-- Force activate: kirim parameter force=1 --}}
                                        @if($action['key'] === 'activate' && $tenant->status === \App\Enums\TenantStatus::Pending)
                                        <input type="hidden" name="force" value="1">
                                        @endif

                                        <button type="submit" :disabled="submitting"
                                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed shadow-sm
                                            @if($action['style'] === 'green') bg-green-600 text-white hover:bg-green-500
                                            @elseif($action['style'] === 'blue') bg-blue-600 text-white hover:bg-blue-500
                                            @elseif($action['style'] === 'yellow') bg-amber-500 text-white hover:bg-amber-400
                                            @elseif($action['style'] === 'red') bg-red-600 text-white hover:bg-red-500
                                            @else bg-slate-600 text-white hover:bg-slate-500 @endif">
                                            <span x-show="!submitting">
                                                @if($action['style'] === 'green')
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @elseif($action['style'] === 'red')
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                                @elseif($action['style'] === 'yellow')
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                @else
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                                @endif
                                                {{ $action['label'] }}
                                            </span>
                                            <span x-show="submitting" class="flex items-center gap-1.5">
                                                <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                Memproses...
                                            </span>
                                        </button>
                                    </form>
                                    @endif

                                    @endforeach

                                    {{-- Tutup panel --}}
                                    <button @click="open = false; submitting = false"
                                        class="ml-auto inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors py-2.5">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tutup
                                    </button>

                                </div>
                                @else
                                <p class="text-xs text-slate-400 italic">Tidak ada aksi yang tersedia untuk status <strong>{{ $tenant->status->label() }}</strong>.</p>
                                @endif

                            </div>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <p class="text-sm text-slate-400">Belum ada tenant yang terdaftar.</p>
                        </td>
                    </tr>
                </tbody>
                @endforelse
            </table>
        </div>
        @if($tenants->count() > 0)
        <div class="border-t border-slate-100 px-5 py-3">
            <p class="text-xs text-slate-400">Total {{ $metrics['total'] }} tenant — {{ $metrics['active'] }} aktif, {{ $metrics['trial'] }} trial, {{ $metrics['expired'] }} expired</p>
        </div>
        @endif
    </div>

</div>

<script>
function aktivasiPage() {
    return {
        search: '',
        filterStatus: '',
    }
}
</script>
@endsection