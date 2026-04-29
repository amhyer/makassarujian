@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Daftar Tagihan</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola invoice dan riwayat tagihan langganan.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-slate-500">Total Tagihan Pending</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $invoices->where('status', \App\Enums\Billing\InvoiceStatus::Pending)->count() }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-slate-500">Total Terbayar</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">Rp {{ number_format($invoices->where('status', \App\Enums\Billing\InvoiceStatus::Paid)->sum('total_amount'), 0, ',', '.') }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-slate-500">Jatuh Tempo (7 Hari)</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">{{ $invoices->where('due_date', '<', now()->addDays(7))->where('status', 'pending')->count() }}</dd>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300">
                <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Invoice #</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Tenant</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Paket</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Jumlah</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Status</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Jatuh Tempo</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($invoices as $invoice)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ $invoice->invoice_number }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $invoice->tenant->name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $invoice->subscription->plan_name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                                {{ $invoice->status === \App\Enums\Billing\InvoiceStatus::Paid ? 'bg-green-50 text-green-700 ring-green-600/20' : 
                                   ($invoice->status === \App\Enums\Billing\InvoiceStatus::Pending ? 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10') }} ring-1 ring-inset">
                                {{ $invoice->status->value }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $invoice->due_date->format('d M Y') }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <a href="{{ route('billing.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-sm text-slate-500 italic">Belum ada data tagihan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection