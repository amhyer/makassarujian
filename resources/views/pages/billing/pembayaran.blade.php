@extends('layouts.app')

@section('content')
<div x-data="paymentPage()" class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Konfirmasi Pembayaran</h2>
            <p class="mt-1 text-sm text-slate-500">Verifikasi bukti transfer dan kelola riwayat transaksi.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300">
                <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Waktu</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Tenant</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Invoice</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Metode</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Jumlah</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Status</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($payments as $payment)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-500 sm:pl-6">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900">{{ $payment->tenant->name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $payment->invoice->invoice_number }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $payment->method->value }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 font-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                                {{ $payment->status === \App\Enums\Billing\PaymentStatus::Approved ? 'bg-green-50 text-green-700 ring-green-600/20' : 
                                   ($payment->status === \App\Enums\Billing\PaymentStatus::PendingVerification ? 'bg-blue-50 text-blue-700 ring-blue-600/20' : 
                                   ($payment->status === \App\Enums\Billing\PaymentStatus::Rejected ? 'bg-red-50 text-red-700 ring-red-600/20' : 'bg-yellow-50 text-yellow-800 ring-yellow-600/20')) }} ring-1 ring-inset">
                                {{ $payment->status->value }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            @role('Super Admin')
                                @if($payment->status === \App\Enums\Billing\PaymentStatus::PendingVerification)
                                <button @click="showVerifyModal({{ $payment }})" class="text-indigo-600 hover:text-indigo-900">Verifikasi</button>
                                @endif
                            @else
                                @if($payment->status === \App\Enums\Billing\PaymentStatus::Pending)
                                <button @click="showUploadModal({{ $payment }})" class="text-indigo-600 hover:text-indigo-900">Upload Bukti</button>
                                @endif
                            @endrole
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-sm text-slate-500 italic">Belum ada data pembayaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $payments->links() }}
        </div>
    </div>

    <!-- Verify Modal (Super Admin) -->
    <template x-if="verifyModalOpen">
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                    <h3 class="text-base font-semibold leading-6 text-slate-900 mb-4">Verifikasi Pembayaran</h3>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-bold">Tenant</p>
                            <p class="text-sm font-medium" x-text="selectedPayment.tenant.name"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-bold">Jumlah</p>
                            <p class="text-sm font-medium" x-text="formatCurrency(selectedPayment.amount)"></p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <p class="text-xs text-slate-500 uppercase font-bold mb-2">Bukti Bayar</p>
                        <div class="border rounded-lg p-2 bg-slate-50 flex justify-center">
                            <img :src="`/billing/payments/${selectedPayment.id}/proof/${selectedPayment.proofs[0].id}/download`" class="max-h-64 rounded shadow-sm">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <form :action="`/billing/payments/${selectedPayment.id}/approve`" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full inline-flex justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Setujui</button>
                        </form>
                        <button @click="rejectOpen = true" class="flex-1 inline-flex justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Tolak</button>
                        <button @click="verifyModalOpen = false" class="flex-1 inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Tutup</button>
                    </div>

                    <div x-show="rejectOpen" class="mt-4 p-4 bg-red-50 rounded-lg border border-red-100">
                        <form :action="`/billing/payments/${selectedPayment.id}/reject`" method="POST">
                            @csrf
                            <label class="block text-sm font-medium text-red-700">Alasan Penolakan</label>
                            <textarea name="reason" required class="mt-1 block w-full rounded-md border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"></textarea>
                            <div class="mt-3 flex justify-end">
                                <button type="submit" class="text-sm font-bold text-red-700 hover:underline">Konfirmasi Tolak</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Upload Modal (School Admin) -->
    <template x-if="uploadModalOpen">
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:p-6">
                    <h3 class="text-base font-semibold leading-6 text-slate-900 mb-4">Unggah Bukti Bayar</h3>
                    
                    <form :action="`/billing/payments/${selectedPayment.id}/proof`" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700">Pilih File (JPG/PNG/PDF)</label>
                            <input type="file" name="proof" required class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        
                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Unggah Sekarang</button>
                            <button @click="uploadModalOpen = false" type="button" class="flex-1 inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function paymentPage() {
    return {
        verifyModalOpen: false,
        uploadModalOpen: false,
        rejectOpen: false,
        selectedPayment: null,
        showVerifyModal(payment) {
            this.selectedPayment = payment;
            this.verifyModalOpen = true;
            this.rejectOpen = false;
        },
        showUploadModal(payment) {
            this.selectedPayment = payment;
            this.uploadModalOpen = true;
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
        }
    }
}
</script>
@endpush
@endsection