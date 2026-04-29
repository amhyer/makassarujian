@props([
    'title',
    'id'
])

<div class="bg-white rounded-lg shadow-sm border border-slate-200">
    <div class="px-4 py-3 border-b border-slate-200">
        <h3 class="font-semibold text-slate-800">{{ $title }}</h3>
    </div>
    <div class="p-4">
        <!-- Skeleton Loading State -->
        <div id="{{ $id }}-skeleton" class="animate-pulse flex space-x-4 items-end h-[250px] w-full justify-center pb-4">
            <div class="w-10 bg-slate-200 rounded h-1/2"></div>
            <div class="w-10 bg-slate-200 rounded h-3/4"></div>
            <div class="w-10 bg-slate-200 rounded h-full"></div>
            <div class="w-10 bg-slate-200 rounded h-1/4"></div>
            <div class="w-10 bg-slate-200 rounded h-2/3"></div>
            <div class="w-10 bg-slate-200 rounded h-1/3"></div>
        </div>

        <div id="{{ $id }}" class="hidden"></div>
    </div>
</div>
