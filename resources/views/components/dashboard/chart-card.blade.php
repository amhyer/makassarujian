@props([
    'title',
    'id'
])

<div class="bg-white/95 rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70">
        <h3 class="font-semibold text-slate-800">{{ $title }}</h3>
    </div>
    <div class="p-5">
        <!-- Skeleton Loading State -->
        <div id="{{ $id }}-skeleton" class="animate-pulse flex space-x-4 items-end h-[250px] w-full justify-center pb-4">
            <div class="w-10 bg-slate-200 rounded-lg h-1/2"></div>
            <div class="w-10 bg-slate-200 rounded-lg h-3/4"></div>
            <div class="w-10 bg-slate-200 rounded-lg h-full"></div>
            <div class="w-10 bg-slate-200 rounded-lg h-1/4"></div>
            <div class="w-10 bg-slate-200 rounded-lg h-2/3"></div>
            <div class="w-10 bg-slate-200 rounded-lg h-1/3"></div>
        </div>

        <div id="{{ $id }}" class="hidden"></div>
    </div>
</div>
