<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} - Makassar Ujian</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans antialiased text-slate-900"
    x-data="{
        sidebarOpen: false,
        desktopSidebarOpen: true,
        toasts: [],
        addToast(msg, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, msg, type });
            setTimeout(() => this.removeToast(id), 4500);
        },
        removeToast(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
    }"
    x-init="
        @if(session('success')) addToast(@js(session('success')), 'success'); @endif
        @if(session('error'))   addToast(@js(session('error')), 'error'); @endif
    "
>

    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/80"></div>
        <div class="fixed inset-0 flex">
            <div x-show="sidebarOpen" x-transition class="relative mr-16 flex w-full max-w-xs flex-1">
                <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                    <button @click="sidebarOpen = false" type="button" class="-m-2.5 p-2.5">
                        <span class="sr-only">Close sidebar</span>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Sidebar content for mobile -->
                <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4 shadow-xl">
                    <div class="flex h-16 shrink-0 items-center border-b border-slate-100">
                        <span class="text-xl font-bold tracking-tight text-indigo-600">Makassar Ujian</span>
                    </div>
                    <nav class="flex flex-1 flex-col">
                        <ul role="list" class="flex flex-1 flex-col gap-y-7">
                            <li>
                                @include('layouts.sidebar-menu')
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop sidebar -->
    <div :class="desktopSidebarOpen ? 'lg:w-72' : 'lg:w-20'" class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col transition-all duration-300">
        <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-slate-200 bg-white" :class="desktopSidebarOpen ? 'px-6' : 'px-3'">
            <div class="flex h-16 shrink-0 items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-x-2 w-full" :class="!desktopSidebarOpen && 'justify-center'">
                    <svg class="h-8 w-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200 class="text-xl font-bold tracking-tight text-indigo-600">Makassar Ujian</span>
                </a>
            </div>
            <nav class="flex flex-1 flex-col">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                        @include('layouts.sidebar-menu')
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Main Content wrapper -->
    <div :class="desktopSidebarOpen ? 'lg:pl-72' : 'lg:pl-20'" class="flex flex-col h-screen transition-all duration-300">
        <!-- Top Navigation -->
        <div
            class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-slate-200 bg-white/80 backdrop-blur-md px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
            <!-- Mobile hamburger -->
            <button @click="sidebarOpen = true" type="button"
                class="-m-2.5 p-2.5 text-slate-700 lg:hidden hover:text-indigo-600 hover:bg-slate-100 rounded-full active:scale-90 transition-all duration-200">
                <span class="sr-only">Open sidebar</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <!-- Desktop hamburger -->
            <button @click="desktopSidebarOpen = !desktopSidebarOpen" type="button"
                class="-m-2.5 p-2.5 text-slate-700 hidden lg:block hover:text-indigo-600 hover:bg-slate-100 rounded-full active:scale-90 transition-all duration-200">
                <span class="sr-only">Toggle sidebar</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <!-- Separator -->
            <div class="h-6 w-px bg-slate-200 lg:hidden" aria-hidden="true"></div>

            <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                <div class="flex flex-1"></div>
                <div class="flex items-center gap-x-4 lg:gap-x-6">

                    <!-- Profile dropdown -->
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false" type="button"
                            class="-m-1.5 flex items-center p-1.5 group hover:bg-slate-50 rounded-full active:scale-95 transition-all duration-200"
                            id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            <span class="sr-only">Open user menu</span>
                            @if(Auth::user()->avatar)
                                <img class="h-8 w-8 rounded-full bg-indigo-100 object-cover" src="{{ Storage::url(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                            @else
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold group-hover:bg-indigo-200 group-hover:text-indigo-800 transition-colors">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="hidden lg:flex lg:items-center">
                                <span
                                    class="ml-4 text-sm font-semibold leading-6 text-slate-900 group-hover:text-indigo-600 transition-colors"
                                    aria-hidden="true">{{ Auth::user()->name }}</span>
                                <svg class="ml-2 h-5 w-5 text-slate-400 group-hover:text-indigo-600 transition-colors"
                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>

                        <div x-show="userMenuOpen" x-transition.opacity
                            class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-slate-900/5 focus:outline-none"
                            role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1"
                            style="display: none;">
                            <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-sm leading-6 text-slate-900 hover:bg-indigo-50 hover:text-indigo-600 transition-colors" role="menuitem" tabindex="-1">Profil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-3 py-2 text-sm leading-6 text-slate-900 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                                    role="menuitem" tabindex="-1">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ IMPERSONATION BANNER ════════════════════════════════════ -->
        @if(session('impersonating'))
        @php $impersonatedTenantId = session('impersonated_tenant'); @endphp
        <div class="sticky top-16 z-30 bg-amber-500 text-white px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-4 shadow-md">
            <div class="flex items-center gap-2.5">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <p class="text-sm font-semibold">
                    ⚠️ Anda sedang <span class="underline">login sebagai admin sekolah</span>.
                    Aksi kritis (aktivasi, expire, billing) dinonaktifkan selama sesi ini.
                </p>
            </div>
            <form action="{{ route('tenants.schools.stop-impersonate') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white/20 hover:bg-white/30 px-3 py-1.5 text-sm font-semibold transition-colors border border-white/30">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                    Hentikan & Kembali
                </button>
            </form>
        </div>
        @endif

        <main class="py-10 flex-1 overflow-auto">
            @if(isset($tenant_status) && $tenant_status === 'expired')
            <div class="rounded-md bg-red-50 p-4 mb-6 mx-4 sm:mx-6 lg:mx-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Akun Anda telah expired. Silakan lakukan pembayaran.</h3>
                    </div>
                </div>
            </div>
            @endif

            <div class="px-4 sm:px-6 lg:px-8 {{ (isset($tenant_status) && $tenant_status === 'expired') ? 'pointer-events-none opacity-50 select-none' : '' }}">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- ═══ TOAST NOTIFICATION SYSTEM ═══════════════════════════════════ -->
    <div class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 w-80" aria-live="polite">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
                class="flex items-start gap-3 rounded-xl p-4 text-white shadow-xl ring-1 ring-white/10">
                <template x-if="toast.type === 'success'">
                    <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </template>
                <p class="text-sm font-medium flex-1" x-html="toast.msg"></p>
                <button @click="removeToast(toast.id)" class="shrink-0 hover:opacity-70 transition-opacity">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

</body>

</html>