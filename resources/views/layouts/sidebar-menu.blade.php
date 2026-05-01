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

    <!-- ═══ Ujian ══════════════════════════════════════════════════════════ -->
    <li x-show="desktopSidebarOpen" class="px-2 pt-4">
        <div class="text-xs font-semibold leading-6 text-slate-400">Ujian</div>
    </li>
    <li>
        <a href="{{ route('ujian.bank-soal') }}"
           class="{{ is_active('ujian.bank-soal') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('ujian.bank-soal') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Bank Soal</span>
        </a>
    </li>
    <li>
        <a href="{{ route('exams.index') }}"
           class="{{ is_active(['exams.index', 'exams.create', 'exams.edit', 'exams.show']) }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon(['exams.index', 'exams.create', 'exams.edit', 'exams.show']) }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h.01M15 12h.01M10.5 16.5h3M13.5 16.5h-3" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Manajemen Ujian</span>
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
        <a href="{{ route('monitoring.status-server') }}"
           class="{{ is_active('monitoring.status-server') }} group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors duration-150"
           :class="!desktopSidebarOpen && 'justify-center'">
            <svg class="{{ is_active_icon('monitoring.status-server') }} h-6 w-6 shrink-0 transition-colors duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V8.25a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 8.25v7.5A2.25 2.25 0 006.75 18z" />
            </svg>
            <span x-show="desktopSidebarOpen" x-transition.opacity.duration.200>Status Server</span>
        </a>
    </li>
</ul>