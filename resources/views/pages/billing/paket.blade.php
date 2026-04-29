@extends('layouts.app')

@section('content')
<div x-data="planPage()" class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Paket & Harga</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola paket langganan SaaS Makassar Ujian.</p>
        </div>
        @role('Super Admin')
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <button @click="showAddModal = true" type="button" class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Tambah Paket
            </button>
        </div>
        @endrole
    </div>

    <!-- Pricing Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($plans as $plan)
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8 flex flex-col {{ $plan->is_active ? '' : 'opacity-50' }}">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold leading-8 text-slate-900">{{ $plan->name }}</h3>
                @if(!$plan->is_active)
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                @endif
            </div>
            <p class="flex items-baseline gap-x-1">
                <span class="text-4xl font-bold tracking-tight text-slate-900">{{ $plan->formatted_price }}</span>
                <span class="text-sm font-semibold leading-6 text-slate-500">/{{ $plan->billing_cycle->value }}</span>
            </p>
            
            <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-slate-600 flex-1">
                @foreach($plan->features ?? [] as $feature => $value)
                <li class="flex gap-x-3">
                    <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    {{ $feature }}: {{ is_bool($value) ? ($value ? 'Ya' : 'Tidak') : $value }}
                </li>
                @endforeach
            </ul>

            @role('Super Admin')
            <div class="mt-8 flex gap-2">
                <button @click="editPlan({{ $plan }})" class="flex-1 rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Edit</button>
                <form action="{{ route('billing.plans.toggle', $plan) }}" method="POST" class="flex-1">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full rounded-md {{ $plan->is_active ? 'bg-red-50 text-red-700 ring-red-600/20' : 'bg-green-50 text-green-700 ring-green-600/20' }} px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset hover:opacity-75">
                        {{ $plan->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
            @else
            <button class="mt-8 block w-full rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Pilih Paket
            </button>
            @endrole
        </div>
        @endforeach
    </div>

    <!-- Add/Edit Modal -->
    <template x-if="showAddModal || showEditModal">
        <div class="relative z-50">
            <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div @click.away="closeModal()" class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-slate-900" x-text="showEditModal ? 'Edit Paket' : 'Tambah Paket Baru'"></h3>
                            <form :action="showEditModal ? `/billing/plans/${editingPlan.id}` : '/billing/plans'" method="POST" class="mt-4 space-y-4">
                                @csrf
                                <template x-if="showEditModal"><input type="hidden" name="_method" value="PUT"></template>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Nama Paket</label>
                                    <input type="text" name="name" x-model="formData.name" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Slug</label>
                                    <input type="text" name="slug" x-model="formData.slug" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Harga (Rp)</label>
                                        <input type="number" name="price" x-model="formData.price" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Siklus</label>
                                        <select name="billing_cycle" x-model="formData.billing_cycle" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="monthly">Bulanan</option>
                                            <option value="yearly">Tahunan</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Simpan</button>
                                    <button @click="closeModal()" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:col-start-1 sm:mt-0">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function planPage() {
    return {
        showAddModal: false,
        showEditModal: false,
        editingPlan: null,
        formData: {
            name: '',
            slug: '',
            price: 0,
            billing_cycle: 'monthly'
        },
        editPlan(plan) {
            this.editingPlan = plan;
            this.formData = { ...plan };
            this.showEditModal = true;
        },
        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.editingPlan = null;
            this.formData = { name: '', slug: '', price: 0, billing_cycle: 'monthly' };
        }
    }
}
</script>
@endpush
@endsection