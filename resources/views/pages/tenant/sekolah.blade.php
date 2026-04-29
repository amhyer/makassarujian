@extends('layouts.app')

@section('content')
<div
    class="space-y-6"
    x-data="{
        showAddModal: false,
        showEditModal: false,
        editData: { id: '', name: '', domain: '' },
        searchQuery: '',
        filterStatus: '',
        selectedIds: [],
        get allIds() { return Array.from(document.querySelectorAll('.row-checkbox')).map(el => el.value); },
        get allSelected() { return this.selectedIds.length > 0 && this.selectedIds.length === this.allIds.length; },
        toggleAll(checked) { this.selectedIds = checked ? this.allIds : []; },
        openEdit(id, name, domain) {
            this.editData = { id, name, domain, formAction: '/tenants/' + id };
            this.showEditModal = true;
        }
    }"
>

    {{-- ── Flash Messages ─────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 flex items-start gap-3">
        <svg class="h-5 w-5 text-green-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-start gap-3">
        <svg class="h-5 w-5 text-red-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
    @endif
    @if($errors->any())
    <div class="rounded-xl bg-red-50 border border-red-200 p-4">
        <p class="text-sm font-medium text-red-800 mb-1">Terdapat kesalahan validasi:</p>
        <ul class="text-sm text-red-700 list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manajemen Sekolah</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola seluruh sekolah yang terdaftar di platform Makassar Ujian.</p>
        </div>
        <button @click="showAddModal = true"
            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 active:scale-95 transition-all duration-150">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Sekolah
        </button>
    </div>

    {{-- ── Summary Cards ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</span>
            <span class="text-3xl font-bold text-slate-900">{{ $metrics['total'] }}</span>
            <span class="text-xs text-slate-400">Semua sekolah</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-green-600 uppercase tracking-wide">Aktif</span>
            <span class="text-3xl font-bold text-green-600">{{ $metrics['active'] }}</span>
            <span class="text-xs text-slate-400">Berlangganan aktif</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">Trial</span>
            <span class="text-3xl font-bold text-blue-600">{{ $metrics['trial'] }}</span>
            <span class="text-xs text-slate-400">Masa percobaan</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-red-500 uppercase tracking-wide">Expired</span>
            <span class="text-3xl font-bold text-red-500">{{ $metrics['expired'] }}</span>
            <span class="text-xs text-slate-400">Perlu diperpanjang</span>
        </div>
    </div>

    {{-- ── Tabel ────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5">

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-b border-slate-100">
            <div class="relative flex-1 max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
                <input x-model="searchQuery" type="text" placeholder="Cari nama / domain..." class="w-full rounded-lg border-0 bg-slate-50 py-2 pl-9 pr-3 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
            </div>
            <select x-model="filterStatus" class="rounded-lg border-0 bg-slate-50 py-2 pl-3 pr-8 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="trial">Trial</option>
                <option value="active">Aktif</option>
                <option value="expired">Expired</option>
                <option value="suspended">Suspended</option>
            </select>
            {{-- Bulk Action Bar --}}
            <div x-show="selectedIds.length > 0" x-transition class="flex items-center gap-2 ml-auto">
                <span class="text-xs text-slate-500" x-text="selectedIds.length + ' dipilih'"></span>
                <button class="text-xs rounded-lg bg-green-100 text-green-700 px-3 py-1.5 font-medium hover:bg-green-200 transition-colors">Bulk Aktifkan</button>
                <button class="text-xs rounded-lg bg-red-100 text-red-700 px-3 py-1.5 font-medium hover:bg-red-200 transition-colors">Bulk Suspend</button>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-visible">
            <table class="min-w-full">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="py-3 pl-5 pr-3 text-left">
                            <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                @change="toggleAll($event.target.checked)">
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Nama Sekolah</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Domain</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Siswa</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Terdaftar</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Trial Berakhir</th>
                        <th class="relative py-3 pl-3 pr-5"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                @forelse($tenants as $tenant)
                    {{-- Setiap tenant punya tbody sendiri sebagai scope Alpine --}}
                    <tbody x-data="{ menuOpen: false }">
                    {{-- Baris utama --}}
                    <tr class="group border-b border-slate-100 transition-colors duration-150 hover:bg-slate-50/70 {{ $tenant->status === \App\Enums\TenantStatus::Expired ? 'bg-red-50/40' : 'bg-white' }}"
                        x-show="
                            (searchQuery === '' || '{{ strtolower($tenant->name) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($tenant->domain ?? '') }}'.includes(searchQuery.toLowerCase())) &&
                            (filterStatus === '' || filterStatus === '{{ $tenant->status->value }}')
                        ">
                        <td class="py-4 pl-5 pr-3">
                            <input type="checkbox" class="row-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                value="{{ $tenant->id }}" x-model="selectedIds">
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $tenant->name }}</p>
                                    <p class="text-xs text-slate-400">ID: {{ substr($tenant->id, 0, 8) }}...</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $tenant->domain ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $tenant->status->badgeClass() }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $tenant->status->dotColor() }}"></span>
                                {{ $tenant->status->label() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-slate-700">
                            {{ number_format($tenant->users_count) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $tenant->created_at->format('d M Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @if($tenant->trial_ends_at)
                                @php $days = (int) now()->diffInDays($tenant->trial_ends_at, false); @endphp
                                <span class="{{ $days <= 0 ? 'text-red-600 font-semibold' : ($days <= 3 ? 'text-yellow-600 font-semibold' : 'text-slate-500') }}">
                                    {{ $tenant->trial_ends_at->format('d M Y') }}
                                    @if($days > 0)
                                        <span class="text-xs">({{ $days }}h lagi)</span>
                                    @elseif($days === 0)
                                        <span class="text-xs">(Hari ini!)</span>
                                    @else
                                        <span class="text-xs">({{ abs($days) }}h lalu)</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-4 pl-3 pr-5 text-right">
                            {{-- Tombol titik-3: toggle inline menu --}}
                            <button @click="menuOpen = !menuOpen"
                                :class="menuOpen ? 'bg-indigo-50 text-indigo-600' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'"
                                class="rounded-lg p-1.5 transition-all duration-200">
                                <svg class="h-5 w-5 transition-transform duration-200" :class="menuOpen ? 'rotate-90' : ''"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>

                    {{-- Baris menu aksi inline (muncul di bawah baris utama) --}}
                    <tr x-show="menuOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="border-b border-slate-100"
                        style="display:none;">
                        <td colspan="8" class="px-5 pb-4 pt-0">
                            <div class="flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide mr-1">Aksi:</span>

                                {{-- Edit Data --}}
                                <button @click="openEdit('{{ $tenant->id }}', '{{ addslashes($tenant->name) }}', '{{ addslashes($tenant->domain ?? '') }}'); menuOpen = false"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 shadow-sm transition-all duration-150">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                    </svg>
                                    Edit Data
                                </button>

                                {{-- Kelola Status --}}
                                <a href="{{ route('tenants.activation') }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:border-green-300 hover:bg-green-50 hover:text-green-700 shadow-sm transition-all duration-150">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Kelola Status
                                </a>

                                {{-- Divider --}}
                                <span class="h-5 w-px bg-slate-200"></span>

                                {{-- Login Sebagai Admin --}}
                                <form action="{{ route('tenants.schools.impersonate', $tenant->id) }}" method="POST"
                                    @submit.prevent="if(confirm('Login sebagai admin {{ addslashes($tenant->name) }}? Aksi kritis akan diblokir selama sesi impersonation.')) $el.submit()">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500 shadow-sm transition-all duration-150">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                                        </svg>
                                        Login Sebagai Admin
                                    </button>
                                </form>

                                {{-- Tutup --}}
                                <button @click="menuOpen = false" class="ml-auto inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Tutup
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                @empty
                <tbody>
                    <tr>
                        <td colspan="8" class="py-16 text-center">
                            <div class="mx-auto max-w-sm">
                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/></svg>
                                <p class="mt-4 font-semibold text-slate-700">Belum ada data sekolah</p>
                                <p class="mt-1 text-sm text-slate-400">Mulai dengan menambahkan sekolah pertama Anda.</p>
                                <button @click="showAddModal = true" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    Tambah Sekolah
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
                @endforelse
            </table>
        </div>

        @if($tenants->count() > 0)
        <div class="border-t border-slate-100 px-5 py-3">
            <p class="text-xs text-slate-400">Menampilkan {{ $tenants->count() }} dari {{ $metrics['total'] }} sekolah</p>
        </div>
        @endif
    </div>

{{-- ── MODAL TAMBAH ──────────────────────────────────────────────────────── --}}
<div x-show="showAddModal" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
    <div x-show="showAddModal" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showAddModal" @click.away="showAddModal = false" x-transition
            class="relative w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/5 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Tambah Sekolah Baru</h3>
            <p class="text-sm text-slate-400 mb-4">Akun admin sekolah dibuat otomatis. Password ditampilkan sekali setelah berhasil.</p>
            <form action="{{ route('tenants.schools.store') }}" method="POST" class="space-y-4"
                x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Sekolah <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: SMAN 1 Makassar"
                        value="{{ old('name') }}"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Admin Sekolah <span class="text-red-500">*</span></label>
                    <input type="email" name="email_admin" required placeholder="admin@sekolah.com"
                        value="{{ old('email_admin') }}"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
                    @error('email_admin')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Domain <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="domain" placeholder="sman1mks.makassarujian.com"
                        value="{{ old('domain') }}"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
                    @error('domain')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-lg bg-blue-50 border border-blue-200 p-3">
                    <p class="text-xs text-blue-700">🔑 Password admin di-generate otomatis dan ditampilkan sekali setelah berhasil disimpan.</p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showAddModal = false"
                        class="flex-1 rounded-xl bg-slate-100 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" :disabled="submitting"
                        class="flex-1 rounded-xl bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Simpan</span>
                        <span x-show="submitting" class="flex items-center justify-center gap-1.5">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL EDIT ──────────────────────────────────────────────────────────── --}}
<div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
    <div x-show="showEditModal" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showEditModal" @click.away="showEditModal = false" x-transition
            class="relative w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/5 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Edit Sekolah</h3>
            <form :action="editData.formAction" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Sekolah</label>
                    <input type="text" name="name" x-model="editData.name" required
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Domain <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="domain" x-model="editData.domain"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showEditModal = false"
                        class="flex-1 rounded-xl bg-slate-100 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection