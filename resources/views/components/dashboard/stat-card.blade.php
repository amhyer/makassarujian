@props([
    'title',
    'value',
    'icon' => null,
    'subtitle' => null,
    'trend' => null,
    'color' => 'indigo',
])

@php
    $themes = [
        'indigo' => [
            'icon' => 'bg-indigo-50 text-indigo-600 ring-indigo-100',
            'trend' => 'bg-indigo-50 text-indigo-700',
        ],
        'emerald' => [
            'icon' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
            'trend' => 'bg-emerald-50 text-emerald-700',
        ],
        'amber' => [
            'icon' => 'bg-amber-50 text-amber-600 ring-amber-100',
            'trend' => 'bg-amber-50 text-amber-700',
        ],
        'rose' => [
            'icon' => 'bg-rose-50 text-rose-600 ring-rose-100',
            'trend' => 'bg-rose-50 text-rose-700',
        ],
        'sky' => [
            'icon' => 'bg-sky-50 text-sky-600 ring-sky-100',
            'trend' => 'bg-sky-50 text-sky-700',
        ],
    ];
    $theme = $themes[$color] ?? $themes['indigo'];
@endphp

<div class="group bg-white/95 p-6 rounded-2xl shadow-sm border border-slate-200 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
    <div class="flex items-start justify-between mb-4">
        <div class="p-2.5 rounded-xl ring-1 {{ $theme['icon'] }}">
            @if($icon)
                {!! $icon !!}
            @else
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            @endif
        </div>
        @if($trend)
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $theme['trend'] }}">
                {{ $trend }}
            </span>
        @endif
    </div>
    <h3 class="text-sm font-medium text-slate-500">{{ $title }}</h3>
    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</p>
    @if($subtitle)
        <p class="mt-2 text-xs text-slate-500">{{ $subtitle }}</p>
    @endif
</div>
