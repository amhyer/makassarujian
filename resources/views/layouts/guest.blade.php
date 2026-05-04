<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Makassar Ujian') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900"
    x-data="{ isGlobalLoading: false, loadingText: 'Memproses data...' }"
    x-init="
        window.addEventListener('app:loading', (event) => {
            isGlobalLoading = !!event.detail?.state;
            loadingText = event.detail?.message ?? 'Memproses data...';
        });
        window.addEventListener('load', () => isGlobalLoading = false);
    "
>
    <div
        x-show="isGlobalLoading"
        x-transition.opacity
        class="app-loading-overlay"
        style="display: none;"
    >
        <div class="app-loading-card">
            <span class="app-loading-spinner" aria-hidden="true"></span>
            <p class="app-loading-text" x-text="loadingText"></p>
        </div>
    </div>

    <div class="flex min-h-full items-center justify-center p-4">
        @yield('content')
    </div>
</body>
</html>
