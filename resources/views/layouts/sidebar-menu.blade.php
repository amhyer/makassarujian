@php
    // Helper functions for cleaner active state logic in the template
    function is_active($routes) {
        $routes = is_array($routes) ? $routes : [$routes];
        foreach ($routes as $route) {
            if (request()->routeIs($route)) {
                return 'bg-indigo-100 text-indigo-600';
            }
        }
        return 'text-slate-700 hover:text-indigo-600 hover:bg-indigo-50';
    }

    function is_active_icon($routes) {
        $routes = is_array($routes) ? $routes : [$routes];
        foreach ($routes as $route) {
            if (request()->routeIs($route)) {
                return 'text-indigo-600';
            }
        }
        return 'text-slate-400 group-hover:text-indigo-600';
    }
@endphp

<ul role="list" class="space-y-1">
    <!-- ═══ General ══════════════════════════════════════════════════════ -->
    <li>
        <a href="{{ route('super-admin.dashboard') }}"
           class="{{ is_active('super-admin.dashboard') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('super-admin.dashboard') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Dashboard</span>
        </a>
    </li>

    <!-- ═══ Manajemen Tenant ═════════════════════════════════════════════ -->
    <li x-show="desktopSidebarOpen" class="px-2 pt-4">
        <div class="text-xs font-semibold leading-6 text-slate-400">Manajemen Tenant</div>
    </li>
    <li>
        <a href="{{ route('tenants.schools') }}"
           class="{{ is_active(['tenants.schools', 'tenants.schools.*']) }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon(['tenants.schools', 'tenants.schools.*']) }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Sekolah</span>
        </a>
    </li>
    <li>
        <a href="{{ route('superadmin.user-management.admin-sekolah') }}"
           class="{{ is_active('superadmin.user-management.admin-sekolah') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.user-management.admin-sekolah') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962a3.75 3.75 0 015.25 0m-5.25 0a3.75 3.75 0 00-5.25 0M3 13.255v1.172c0 .92.75 1.67 1.67 1.67h16.66c.92 0 1.67-.75 1.67-1.67v-1.172c0-.92-.75-1.67-1.67-1.67h-16.66A1.67 1.67 0 003 13.255z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Admin Sekolah</span>
        </a>
    </li>
    <li>
        <a href="{{ route('tenants.fkkg') }}"
           class="{{ is_active('tenants.fkkg') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('tenants.fkkg') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 012.25 10.875v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>FKKG</span>
        </a>
    </li>
    <li>
        <a href="{{ route('superadmin.user-management.admin-fkgg') }}"
           class="{{ is_active('superadmin.user-management.admin-fkgg') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.user-management.admin-fkgg') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-4.67c.12-.318.239-.636.354-.96" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Admin FKKG</span>
        </a>
    </li>
    <li>
        <a href="{{ route('tenants.activation') }}"
           class="{{ is_active('tenants.activation') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('tenants.activation') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Aktivasi</span>
        </a>
    </li>

    <!-- ═══ Billing ══════════════════════════════════════════════════════ -->
    <li x-show="desktopSidebarOpen" class="px-2 pt-4">
        <div class="text-xs font-semibold leading-6 text-slate-400">Billing</div>
    </li>
    <li>
        <a href="{{ route('billing.dashboard.revenue') }}"
           class="{{ is_active('billing.dashboard.revenue') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('billing.dashboard.revenue') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.75A.75.75 0 013 4.5h.75m0 0h.75A.75.75 0 015.25 6v.75m0 0v.75A.75.75 0 014.5 8.25h-.75m0 0h-.75A.75.75 0 012.25 7.5v-.75M6 15V7.5a2.25 2.25 0 012.25-2.25h3.75a2.25 2.25 0 012.25 2.25V15m-9.75-4.5h9.75" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Revenue</span>
        </a>
    </li>
    <li>
        <a href="{{ route('billing.plans') }}"
           class="{{ is_active('billing.plans') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('billing.plans') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Paket</span>
        </a>
    </li>
    <li>
        <a href="{{ route('billing.invoices') }}"
           class="{{ is_active(['billing.invoices', 'billing.invoices.*']) }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon(['billing.invoices', 'billing.invoices.*']) }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75c0-.231-.035-.454-.1-.664M6.75 7.5H18a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25-2.25H6.75a2.25 2.25 0 01-2.25-2.25v-9a2.25 2.25 0 012.25-2.25z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Tagihan</span>
        </a>
    </li>
    <li>
        <a href="{{ route('billing.payments') }}"
           class="{{ is_active('billing.payments') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('billing.payments') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Pembayaran</span>
        </a>
    </li>
    <li>
        <a href="{{ route('billing.trials') }}"
           class="{{ is_active('billing.trials') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('billing.trials') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Trial</span>
        </a>
    </li>

    <!-- ═══ Ujian Global ═════════════════════════════════════════════════ -->
    <li x-show="desktopSidebarOpen" class="px-2 pt-4">
        <div class="text-xs font-semibold leading-6 text-slate-400">Ujian Global</div>
    </li>
    <li>
        <a href="{{ route('superadmin.ujian.template') }}"
           class="{{ is_active('superadmin.ujian.template') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('ujian.bank-soal') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Template Ujian</span>
        </a>
    </li>
    <li>
        <a href="{{ route('superadmin.ujian.distribusi') }}"
           class="{{ is_active('superadmin.ujian.distribusi') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.ujian.distribusi') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Distribusi</span>
        </a>
    </li>

    <!-- ═══ Monitoring ═══════════════════════════════════════════════════ -->
    <li x-show="desktopSidebarOpen" class="px-2 pt-4">
        <div class="text-xs font-semibold leading-6 text-slate-400">Monitoring</div>
    </li>
    <li>
        <a href="{{ route('monitoring.ujian-berlangsung') }}"
           class="{{ is_active('monitoring.ujian-berlangsung') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('monitoring.ujian-berlangsung') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.375 1.5-10.5 10.5 0 00-9.25-5.131 1.5 1.5 0 01-1.5-1.5v-2.253a1.5 1.5 0 011.5-1.5h16.5a1.5 1.5 0 011.5 1.5v2.253a1.5 1.5 0 01-1.5 1.5-10.5 10.5 0 00-9.25 5.131 3 3 0 01-.375-1.5v-1.007z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Ujian Berlangsung</span>
        </a>
    </li>
    <li>
        <a href="{{ route('superadmin.monitoring.aktivitas-siswa') }}"
           class="{{ is_active('superadmin.monitoring.aktivitas-siswa') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.monitoring.aktivitas-siswa') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 10-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 013.15 0v1.5m-3.15 0l.075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 013.15 0V15M6.9 7.575a1.575 1.575 0 10-3.15 0v8.175a6.75 6.75 0 006.75 6.75h2.018a5.25 5.25 0 005.25-5.25v-2.838a3.75 3.75 0 00-3.75-3.75H6.9V7.575z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Aktivitas Siswa</span>
        </a>
    </li>
    <li>
        <a href="{{ route('monitoring.status-server') }}"
           class="{{ is_active('monitoring.status-server') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('monitoring.status-server') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V8.25a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 8.25v7.5A2.25 2.25 0 006.75 18z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Status Server</span>
        </a>
    </li>

    <!-- ═══ Sistem ═════════════════════════════════════════════════════════ -->
    <li x-show="desktopSidebarOpen" class="px-2 pt-4">
        <div class="text-xs font-semibold leading-6 text-slate-400">Sistem</div>
    </li>
    <li>
        <a href="{{ route('superadmin.sistem.konfigurasi') }}"
           class="{{ is_active('superadmin.sistem.konfigurasi') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.sistem.konfigurasi') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.438.995s.145.755.438.995l1.003.827c.48.398.668 1.03.26 1.431l-1.296-2.247a1.125 1.125 0 01-1.37.49l-1.217-.456c-.355-.133-.75-.072-1.075.124a6.57 6.57 0 01-.22.127c-.331.183-.581.495-.645.87l-.213 1.281c-.09.543-.56.94-1.11.94h-2.593c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a6.52 6.52 0 01-.22-.127c-.324-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 01-1.37-.49l-1.296-2.247a1.125 1.125 0 01.26-1.431l1.003-.827c.293-.24.438.613-.438-.995s-.145-.755-.438-.995l-1.003-.827c-.48-.398-.668-1.03-.26-1.431l1.296-2.247a1.125 1.125 0 011.37-.49l1.217.456c.355.133.75.072 1.075.124.073-.044.146-.087.22-.127.331-.183.581-.495.645-.87l.213-1.281z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Konfigurasi</span>
        </a>
    </li>
    <li>
        <a href="{{ route('superadmin.sistem.role-permission') }}"
           class="{{ is_active('superadmin.sistem.role-permission') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.sistem.role-permission') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Role & Permission</span>
        </a>
    </li>
    <li>
        <a href="{{ route('superadmin.sistem.audit-log') }}"
           class="{{ is_active('superadmin.sistem.audit-log') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.sistem.audit-log') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125v-1.5c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h.008v.008h-.008v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5v-7.5a2.25 2.25 0 012.25-2.25h9a2.25 2.25 0 012.25 2.25v7.5" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Audit Log</span>
        </a>
    </li>

    <!-- ═══ Support ════════════════════════════════════════════════════════ -->
    <li x-show="desktopSidebarOpen" class="px-2 pt-4">
        <div class="text-xs font-semibold leading-6 text-slate-400">Support</div>
    </li>
    <li>
        <a href="{{ route('superadmin.support.tiket') }}"
           class="{{ is_active('superadmin.support.tiket') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.support.tiket') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12v.75m0 3v.75m0 3v.75m0 3V18m-3 .75h18A2.25 2.25 0 0021 16.5V7.5A2.25 2.25 0 0018.75 5.25h-18A2.25 2.25 0 003 7.5v9A2.25 2.25 0 005.25 18.75m13.5-9h-6" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Tiket Bantuan</span>
        </a>
    </li>
    <li>
        <a href="{{ route('superadmin.support.broadcast') }}"
           class="{{ is_active('superadmin.support.broadcast') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('superadmin.support.broadcast') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688 0-1.25-.562-1.25-1.25s.562-1.25 1.25-1.25 1.25.562 1.25 1.25-.562 1.25-1.25 1.25z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Broadcast</span>
        </a>
    </li>
</ul>