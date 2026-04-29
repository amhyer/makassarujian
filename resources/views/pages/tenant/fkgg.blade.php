@extends('layouts.app')

@section('content')
<div x-data="fkggPage()" x-init="initModal({{ $errors->any() ? 'true' : 'false' }})">
<div class="space-y-6">

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
    @if($errors->any())
    <div class="rounded-xl bg-red-50 border border-red-200 p-4">
        <p class="text-sm font-medium text-red-800 mb-1">Terdapat kesalahan validasi:</p>
        <ul class="text-sm text-red-700 list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manajemen FKGG</h1>
            <p class="mt-1 text-sm text-slate-500">FKGG adalah penyedia konten soal TKA dan tryout untuk seluruh sekolah di platform.</p>
        </div>
        <button @click="openAdd()"
            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 active:scale-95 transition-all duration-150">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah FKGG
        </button>
    </div>

    {{-- ── Summary Cards ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total FKGG</span>
            <span class="text-3xl font-bold text-slate-900">{{ $metrics['total'] }}</span>
            <span class="text-xs text-slate-400">Terdaftar</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-purple-600 uppercase tracking-wide">Total Soal</span>
            <span class="text-3xl font-bold text-purple-600">{{ $metrics['questions'] ?: '—' }}</span>
            <span class="text-xs text-slate-400">Coming soon</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">Tryout Aktif</span>
            <span class="text-3xl font-bold text-blue-600">{{ $metrics['tryouts'] ?: '—' }}</span>
            <span class="text-xs text-slate-400">Coming soon</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-green-600 uppercase tracking-wide">Sekolah Terhubung</span>
            <span class="text-3xl font-bold text-green-600">{{ $metrics['schools'] ?: '—' }}</span>
            <span class="text-xs text-slate-400">Coming soon</span>
        </div>
    </div>

    {{-- ── Tabel ────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
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
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Nama FKGG</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Domain</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Tipe</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Soal</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Tryout</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Sekolah Terhubung</th>
                        <th class="relative py-3 pl-3 pr-5"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tenants as $tenant)
                    <tr class="group transition-colors duration-150 hover:bg-slate-50 bg-white"
                        x-show="
                            (searchQuery === '' || '{{ strtolower($tenant->name) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($tenant->domain ?? '') }}'.includes(searchQuery.toLowerCase())) &&
                            (filterStatus === '' || filterStatus === '{{ $tenant->status->value }}')
                        ">
                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700 font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $tenant->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $tenant->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $tenant->domain ?? '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $tenant->status->badgeClass() }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $tenant->status->dotColor() }}"></span>
                                {{ $tenant->status->label() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">
                                Official TKA Provider
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-400 italic">Coming Soon</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-400 italic">Coming Soon</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-400 italic">Coming Soon</td>
                        <td class="whitespace-nowrap py-4 pl-3 pr-5 text-right">
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false"
                                    class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"/>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition
                                    class="absolute right-0 z-20 mt-1 w-52 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-slate-900/5 py-1"
                                    style="display:none;">
                                    <button @click="openEdit('{{ $tenant->id }}', '{{ addslashes($tenant->name) }}', '{{ addslashes($tenant->domain ?? '') }}'); open = false"
                                        class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                        Edit Data
                                    </button>
                                    <button onclick="alert('Bank Soal — Coming Soon')" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zm0 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zm0 6.75a.75.75 0 110-1.5.75.75 0 010 1.5z"/></svg>
                                        Lihat Bank Soal
                                    </button>
                                    <button onclick="alert('Tryout — Coming Soon')" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75"/></svg>
                                        Lihat Tryout
                                    </button>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <a href="{{ route('tenants.activation') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Kelola Status
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-16 text-center">
                            <div class="mx-auto max-w-sm">
                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75"/></svg>
                                <p class="mt-4 font-semibold text-slate-700">Belum ada FKGG terdaftar</p>
                                <p class="mt-2 text-sm text-slate-400">FKGG adalah pihak pembuat soal TKA dan tryout. Mereka mendistribusikan konten ke seluruh sekolah yang berlangganan.</p>
                                <button @click="openAdd()" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    Tambah FKGG
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>{{-- end .space-y-6 --}}

{{-- ── MODAL TAMBAH ──────────────────────────────────────────────────────────── --}}
<div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
    <div x-show="showAddModal" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showAddModal" @click.outside="closeAdd()" x-transition
            class="relative w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/5 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Tambah FKGG Baru</h3>
            <p class="text-sm text-slate-400 mb-4">Akun admin FKGG dibuat otomatis. Password ditampilkan sekali setelah berhasil.</p>
            <form action="{{ route('tenants.fkkg.store') }}" method="POST" class="space-y-4"
                x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama FKGG <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: FKGG Wilayah Sulawesi Selatan"
                        value="{{ old('name') }}"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Admin FKGG <span class="text-red-500">*</span></label>
                    <input type="email" name="email_admin" required placeholder="admin@fkgg.com"
                        value="{{ old('email_admin') }}"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
                    @error('email_admin')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Domain <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="domain" placeholder="fkgg.makassarujian.com"
                        value="{{ old('domain') }}"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400">
                    @error('domain')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-lg bg-blue-50 border border-blue-200 p-3">
                    <p class="text-xs text-blue-700">🔑 Password admin di-generate otomatis dan ditampilkan sekali setelah berhasil disimpan.</p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="closeAdd()"
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
<div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
    <div x-show="showEditModal" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showEditModal" @click.outside="closeEdit()" x-transition
            class="relative w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/5 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Edit FKGG</h3>
            <form :action="editData.formAction" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama FKGG</label>
                    <input type="text" name="name" x-model="editData.name" required
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Domain <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="domain" x-model="editData.domain"
                        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="closeEdit()"
                        class="flex-1 rounded-xl bg-slate-100 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>{{-- end x-data --}}

<script>
    function fkggPage() {
        return {
            showAddModal: false,
            showEditModal: false,
            editData: { id: '', name: '', domain: '', formAction: '' },
            searchQuery: '',
            filterStatus: '',

            initModal(hasErrors) {
                if (hasErrors) {
                    this.showAddModal = true;
                }
            },

            openAdd() {
                this.showAddModal = true;
            },

            closeAdd() {
                this.showAddModal = false;
            },

            openEdit(id, name, domain) {
                this.editData = { id: id, name: name, domain: domain, formAction: '/tenants/fkkg/' + id }; // Notice: Make sure this route is valid or update as necessary
                this.showEditModal = true;
            },
            
            closeEdit() {
                this.showEditModal = false;
            }
        }
    }
</script>
@endsection