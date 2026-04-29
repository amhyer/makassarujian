@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Revenue Dashboard</h1>
                <p class="text-slate-500 text-sm">Real-time SaaS Business Intelligence</p>
            </div>
            <div class="flex gap-2">
                <button onclick="window.location.reload()" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh
                </button>
            </div>
        </div>

        {{-- 🔥 METRICS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-dashboard.stat-card 
                title="MRR" 
                :value="'Rp ' . number_format($metrics['mrr'], 0, ',', '.')" 
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            />
            <x-dashboard.stat-card 
                title="ARR" 
                :value="'Rp ' . number_format($metrics['arr'], 0, ',', '.')" 
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>'
            />
            <x-dashboard.stat-card 
                title="Total Revenue" 
                :value="'Rp ' . number_format($metrics['total_revenue'], 0, ',', '.')" 
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            />
            <x-dashboard.stat-card 
                title="Pending Payment" 
                :value="'Rp ' . number_format($metrics['pending_revenue'], 0, ',', '.')" 
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- 📈 CHART --}}
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-900">Revenue (30 Hari Terakhir)</h2>
                    <span class="text-xs font-medium text-slate-400">Aggregated Daily</span>
                </div>
                <div class="h-80">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            {{-- 📊 SUBSCRIPTION STATUS --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <h2 class="text-lg font-bold text-slate-900 mb-6">Subscription Status</h2>

                <div class="space-y-4">
                    @foreach($subscriptions as $status => $count)
                        <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full @if($status == 'active') bg-indigo-500 @elseif($status == 'trial') bg-amber-500 @elseif($status == 'expired') bg-rose-500 @else bg-slate-400 @endif"></div>
                                <p class="text-sm font-medium text-slate-600 capitalize">{{ $status }}</p>
                            </div>
                            <p class="text-lg font-bold text-slate-900">{{ $count }}</p>
                        </div>
                    @endforeach
                    
                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <div class="flex justify-between items-center mb-2 text-sm">
                            <span class="text-slate-500">Churn Rate</span>
                            <span class="font-bold text-rose-600">{{ $metrics['churn_rate'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="bg-rose-500 h-1.5 rounded-full" style="width: {{ $metrics['churn_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const data = @json($chart);
    const labels = data.map(i => i.date);
    const values = data.map(i => i.total);

    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Gradient for the line chart
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: values,
                borderColor: '#4f46e5',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>
@endpush
