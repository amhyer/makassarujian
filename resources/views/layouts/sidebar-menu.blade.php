<ul role="list" class="-mx-2 space-y-1">
    <!-- Dashboard -->
    <li>
        <a href="{{ route('dashboard') ?? '#' }}"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
            <svg class="h-6 w-6 shrink-0 {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600' }}" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
            </svg>
            Dashboard
        </a>
    </li>

    <!-- Manajemen Tenant -->
    <li x-data="{ open: {{ request()->is('tenants*') ? 'true' : 'false' }} }">
        <button @click="open = !open"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group"
            :class="open ? 'bg-slate-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600'">
            <svg class="h-6 w-6 shrink-0 transition-colors duration-200"
                :class="open ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            Manajemen Tenant
            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200"
                :class="open ? 'text-indigo-600 rotate-180' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <ul x-show="open" x-transition class="mt-1 px-2 space-y-1">
            <li><a href="{{ route('tenants.schools') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('tenants.schools') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Sekolah</a>
            </li>
            <li><a href="{{ route('tenants.fkkg') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('tenants.fkkg') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">FKGG</a>
            </li>
            <li><a href="{{ route('tenants.activation') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('tenants.activation') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Aktivasi & Status</a></li>
        </ul>
    </li>

    <!-- Billing & Subscription -->
    <li x-data="{ open: {{ request()->is('billing*') ? 'true' : 'false' }} }">
        <button @click="open = !open"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group"
            :class="open ? 'bg-slate-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600'">
            <svg class="h-6 w-6 shrink-0 transition-colors duration-200"
                :class="open ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V4.22c0-.756-.728-1.294-1.453-1.096a60.364 60.364 0 00-15.797 2.102c-.75.225-1.25.925-1.25 1.71v10.054c0 .784.5 1.484 1.25 1.71z" />
            </svg>
            Billing & Subscription
            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200"
                :class="open ? 'text-indigo-600 rotate-180' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <ul x-show="open" x-transition class="mt-1 px-2 space-y-1">
            <li><a href="{{ route('billing.plans') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('billing.plans') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Paket & Harga</a></li>
            <li><a href="{{ route('billing.invoices') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('billing.invoices') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Tagihan</a>
            </li>
            <li><a href="{{ route('billing.payments') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('billing.payments') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Pembayaran</a>
            </li>
            <li><a href="{{ route('billing.trials') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('billing.trials') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Trial Management</a></li>
        </ul>
    </li>

    <!-- Ujian Global -->
    <li x-data="{ open: {{ request()->is('ujian*') ? 'true' : 'false' }} }">
        <button @click="open = !open"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group"
            :class="open ? 'bg-slate-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600'">
            <svg class="h-6 w-6 shrink-0 transition-colors duration-200"
                :class="open ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            Ujian Global
            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200"
                :class="open ? 'text-indigo-600 rotate-180' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <ul x-show="open" x-transition class="mt-1 px-2 space-y-1">
            <li><a href="{{ route('ujian.bank-soal') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('ujian.bank-soal') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Bank Soal</a></li>
            <li><a href="{{ route('questions.create') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('questions.create') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Input Soal Baru</a></li>
            <li><a href="{{ route('ujian.distribusi') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('ujian.distribusi') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Distribusi Soal</a></li>
            <li><a href="{{ route('ujian.template') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('ujian.template') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Template Ujian</a></li>
        </ul>
    </li>

    <!-- Monitoring -->
    <li x-data="{ open: {{ request()->is('monitoring*') ? 'true' : 'false' }} }">
        <button @click="open = !open"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group"
            :class="open ? 'bg-slate-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600'">
            <svg class="h-6 w-6 shrink-0 transition-colors duration-200"
                :class="open ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            Monitoring
            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200"
                :class="open ? 'text-indigo-600 rotate-180' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <ul x-show="open" x-transition class="mt-1 px-2 space-y-1">
            <li><a href="{{ route('monitoring.ujian-berlangsung') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('monitoring.ujian-berlangsung') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Ujian Berlangsung</a></li>
            <li><a href="{{ route('monitoring.aktivitas-siswa') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('monitoring.aktivitas-siswa') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Aktivitas Siswa</a></li>
            <li><a href="{{ route('monitoring.status-server') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('monitoring.status-server') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Status Server</a></li>
        </ul>
    </li>

    <!-- User Management -->
    <li x-data="{ open: {{ request()->is('user-management*') ? 'true' : 'false' }} }">
        <button @click="open = !open"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group"
            :class="open ? 'bg-slate-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600'">
            <svg class="h-6 w-6 shrink-0 transition-colors duration-200"
                :class="open ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
            User Management
            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200"
                :class="open ? 'text-indigo-600 rotate-180' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <ul x-show="open" x-transition class="mt-1 px-2 space-y-1">
            <li><a href="{{ route('user-management.admin-sekolah') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('user-management.admin-sekolah') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Admin Sekolah</a></li>
            <li><a href="{{ route('user-management.admin-fkgg') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('user-management.admin-fkgg') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Admin FKGG</a></li>
        </ul>
    </li>

    <!-- Sistem -->
    <li x-data="{ open: {{ request()->is('sistem*') ? 'true' : 'false' }} }">
        <button @click="open = !open"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group"
            :class="open ? 'bg-slate-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600'">
            <svg class="h-6 w-6 shrink-0 transition-colors duration-200"
                :class="open ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Sistem
            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200"
                :class="open ? 'text-indigo-600 rotate-180' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <ul x-show="open" x-transition class="mt-1 px-2 space-y-1">
            <li><a href="{{ route('sistem.konfigurasi') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('sistem.konfigurasi') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Konfigurasi Global</a></li>
            <li><a href="{{ route('sistem.role-permission') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('sistem.role-permission') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Role & Permission</a></li>
            <li><a href="{{ route('sistem.audit-log') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('sistem.audit-log') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Audit Log</a></li>
        </ul>
    </li>

    <!-- Support & Notifikasi -->
    <li x-data="{ open: {{ request()->is('support*') ? 'true' : 'false' }} }">
        <button @click="open = !open"
            class="flex w-full items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 group"
            :class="open ? 'bg-slate-50 text-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600'">
            <svg class="h-6 w-6 shrink-0 transition-colors duration-200"
                :class="open ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            Support & Notifikasi
            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200"
                :class="open ? 'text-indigo-600 rotate-180' : 'text-slate-400 group-hover:text-indigo-600'" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <ul x-show="open" x-transition class="mt-1 px-2 space-y-1">
            <li><a href="{{ route('support.broadcast') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('support.broadcast') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Broadcast</a>
            </li>
            <li><a href="{{ route('support.tiket') }}"
                    class="block rounded-md py-2 pr-2 pl-9 text-sm leading-6 transition-colors {{ request()->routeIs('support.tiket') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">Tiket Bantuan</a></li>
        </ul>
    </li>
</ul>