@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-slate-900">Detail Tagihan</h3>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $invoice->invoice_number }}</p>
            </div>
            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                {{ $invoice->status === \App\Enums\Billing\InvoiceStatus::Paid ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' }} ring-1 ring-inset">
                {{ $invoice->status->value }}
            </span>
        </div>
        <div class="border-t border-slate-200 px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-slate-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500">Nama Tenant</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">{{ $invoice->tenant->name }}</dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500">Paket Langganan</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">{{ $invoice->subscription->plan_name }}</dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500">Jumlah Tagihan</dt>
                    <dd class="mt-1 text-sm text-slate-900 font-bold sm:mt-0 sm:col-span-2">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500">Jatuh Tempo</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">{{ $invoice->due_date->format('d F Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @if($invoice->status === \App\Enums\Billing\InvoiceStatus::Pending)
    <div class="mt-6 bg-indigo-50 rounded-lg p-6 border border-indigo-100 text-center">
        <h4 class="text-indigo-800 font-bold mb-2 text-lg">Instruksi Pembayaran</h4>
        <p class="text-indigo-600 text-sm mb-4">Silakan lakukan transfer ke salah satu rekening di bawah ini:</p>
        
        <!-- Payment Methods Placeholder -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-indigo-200">
                <p class="text-xs font-bold text-slate-400 uppercase">Bank BCA</p>
                <p class="text-lg font-mono font-bold text-slate-800">1234567890</p>
                <p class="text-xs text-slate-500">a.n. Makassar Ujian</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-indigo-200">
                <p class="text-xs font-bold text-slate-400 uppercase">Bank BRI</p>
                <p class="text-lg font-mono font-bold text-slate-800">9876543210123</p>
                <p class="text-xs text-slate-500">a.n. Makassar Ujian</p>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('billing.payments') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Saya Sudah Bayar (Konfirmasi)
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
