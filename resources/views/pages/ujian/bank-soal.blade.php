@extends('layouts.app')

@section('content')
<div id="app" class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Bank Soal</h2>
            <p class="mt-1 text-sm text-slate-500">Repositori soal ujian yang aman dan terisolasi.</p>
        </div>
    </div>

    <question-stats></question-stats>
    <question-list></question-list>
</div>
@endsection