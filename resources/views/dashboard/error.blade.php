@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[500px] text-center space-y-4">
    <div class="bg-red-100 text-red-500 p-4 rounded-full">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-slate-800">Ups, Terjadi Kesalahan</h2>
    <p class="text-slate-500 max-w-md">
        {{ $message ?? 'Kami tidak dapat memuat dashboard Anda saat ini. Silakan muat ulang halaman atau hubungi dukungan.' }}
    </p>
    <button onclick="window.location.reload()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
        Muat Ulang Halaman
    </button>
</div>
@endsection
