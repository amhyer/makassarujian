@extends('layouts.app')

@section('content')
<div class="dashboard-page" x-data="planManagement()">
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title">Manajemen Paket (Plans)</h2>
            <p class="dashboard-subtitle">Kelola daftar paket langganan, harga, dan limitasi kuota untuk sekolah.</p>
        </div>
        <div>
            <button @click="openCreateModal()" type="button" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm transition-colors">
                <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Paket
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Paket</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Harga & Siklus</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Limitasi</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($plans as $plan)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-slate-900">{{ $plan->name }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">Pengawasan (Proctoring): <span class="{{ $plan->has_proctoring_feature ? 'text-emerald-600 font-semibold' : 'text-slate-400' }}">{{ $plan->has_proctoring_feature ? 'Aktif' : 'Tidak Ada' }}</span></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-900">Rp {{ number_format($plan->price, 0, ',', '.') }}</div>
                            <div class="text-xs text-slate-500">{{ $plan->billing_cycle === 'monthly' ? 'Bulanan' : 'Tahunan' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <div>Siswa: <span class="font-semibold text-slate-700">{{ $plan->student_limit ?? 'Unlimited' }}</span></div>
                            <div>Ujian: <span class="font-semibold text-slate-700">{{ $plan->exam_limit ?? 'Unlimited' }}</span></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('billing.plans.toggle', $plan->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors hover:opacity-80 {{ $plan->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    {{ $plan->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="openEditModal({{ $plan->toJson() }})" class="text-indigo-600 hover:text-indigo-900 mr-4 transition-colors">Edit</button>
                            <form action="{{ route('billing.plans.destroy', $plan->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket ini secara permanen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-900 transition-colors">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500 bg-slate-50/50">
                            Belum ada paket yang dikonfigurasi. Silakan buat paket pertama Anda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create/Edit Plan -->
    <div x-show="isModalOpen" style="display: none;" class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="isModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     @click.away="isModalOpen = false"
                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    
                    <form :action="formAction" method="POST">
                        @csrf
                        <input type="hidden" name="_method" :value="formMethod">
                        
                        <div class="bg-white px-6 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:ml-2 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title" x-text="isEdit ? 'Edit Paket' : 'Buat Paket Baru'"></h3>
                                    
                                    <div class="mt-5 space-y-4">
                                        <!-- Nama Paket -->
                                        <div>
                                            <label class="block text-sm font-medium leading-6 text-slate-900">Nama Paket <span class="text-rose-500">*</span></label>
                                            <input type="text" name="name" x-model="form.name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <!-- Harga -->
                                            <div>
                                                <label class="block text-sm font-medium leading-6 text-slate-900">Harga (Rp) <span class="text-rose-500">*</span></label>
                                                <input type="number" name="price" x-model="form.price" required min="0" class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            <!-- Siklus -->
                                            <div>
                                                <label class="block text-sm font-medium leading-6 text-slate-900">Siklus Penagihan</label>
                                                <select name="billing_cycle" x-model="form.billing_cycle" class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                    <option value="monthly">Bulanan</option>
                                                    <option value="yearly">Tahunan</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <!-- Limit Siswa -->
                                            <div>
                                                <label class="block text-sm font-medium leading-6 text-slate-900">Limit Siswa</label>
                                                <input type="number" name="student_limit" x-model="form.student_limit" min="1" placeholder="Kosong = Unlimited" class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            <!-- Limit Ujian -->
                                            <div>
                                                <label class="block text-sm font-medium leading-6 text-slate-900">Limit Ujian</label>
                                                <input type="number" name="exam_limit" x-model="form.exam_limit" min="1" placeholder="Kosong = Unlimited" class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                        </div>

                                        <!-- Toggles -->
                                        <div class="pt-2 space-y-3">
                                            <label class="relative flex items-start cursor-pointer">
                                                <div class="flex h-6 items-center">
                                                    <input type="checkbox" name="has_proctoring_feature" value="1" x-model="form.has_proctoring_feature" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                                </div>
                                                <div class="ml-3 text-sm leading-6">
                                                    <span class="font-medium text-slate-900">Aktifkan Fitur Pengawasan (Live Proctoring)</span>
                                                    <p class="text-slate-500 text-xs">Izinkan sekolah menggunakan deteksi kecurangan dan web proctor.</p>
                                                </div>
                                            </label>
                                            
                                            <label class="relative flex items-start cursor-pointer">
                                                <div class="flex h-6 items-center">
                                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                                </div>
                                                <div class="ml-3 text-sm leading-6">
                                                    <span class="font-medium text-slate-900">Status Paket Aktif</span>
                                                    <p class="text-slate-500 text-xs">Paket akan ditampilkan di panel pendaftaran sekolah.</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-200">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:ml-3 sm:w-auto">Simpan Data</button>
                            <button type="button" @click="isModalOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('planManagement', () => ({
            isModalOpen: false,
            isEdit: false,
            formAction: '',
            formMethod: 'POST',
            form: {
                name: '',
                price: 0,
                billing_cycle: 'monthly',
                student_limit: '',
                exam_limit: '',
                has_proctoring_feature: false,
                is_active: true
            },
            openCreateModal() {
                this.isEdit = false;
                this.formAction = '{{ route('billing.plans.store') }}';
                this.formMethod = 'POST';
                this.form = { name: '', price: 0, billing_cycle: 'monthly', student_limit: '', exam_limit: '', has_proctoring_feature: false, is_active: true };
                this.isModalOpen = true;
            },
            openEditModal(plan) {
                this.isEdit = true;
                this.formAction = '/billing/plans/' + plan.id;
                this.formMethod = 'PUT';
                this.form = {
                    name: plan.name,
                    price: Math.floor(plan.price), // Menghilangkan desimal berlebih di UI input
                    billing_cycle: plan.billing_cycle,
                    student_limit: plan.student_limit,
                    exam_limit: plan.exam_limit,
                    has_proctoring_feature: plan.has_proctoring_feature,
                    is_active: plan.is_active
                };
                this.isModalOpen = true;
            }
        }));
    });
</script>
@endpush
@endsection