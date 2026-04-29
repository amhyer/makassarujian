@props(['title', 'value', 'icon' => null])

<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
    <div class="flex items-center justify-between mb-4">
        <div class="p-2 bg-slate-50 rounded-lg text-slate-600">
            @if($icon)
                {!! $icon !!}
            @else
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            @endif
        </div>
    </div>
    <h3 class="text-sm font-medium text-slate-500">{{ $title }}</h3>
    <p class="text-2xl font-bold text-slate-900">{{ $value }}</p>
</div>
