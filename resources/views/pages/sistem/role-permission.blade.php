@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="{ showAddRoleModal: false, newRoleName: '', assigningRole: null }">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">Role & Permission</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola hak akses dan wewenang setiap peran di sistem menggunakan Spatie Permission.</p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <button @click="showAddRoleModal = true" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                + Tambah Role
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 ring-1 ring-green-200">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Roles Table --}}
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Daftar Role</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Role</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Guard</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Permissions</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jumlah Permission</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($roles as $role)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-semibold text-slate-900">
                            @php
                                $roleColors = [
                                    'Super Admin' => 'bg-purple-100 text-purple-800',
                                    'School Admin' => 'bg-blue-100 text-blue-800',
                                    'Student' => 'bg-green-100 text-green-800',
                                    'FKKG Admin' => 'bg-orange-100 text-orange-800',
                                    'Proctor' => 'bg-yellow-100 text-yellow-800',
                                ];
                                $roleColor = $roleColors[$role->name] ?? 'bg-slate-100 text-slate-800';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $roleColor }}">
                                {{ $role->name }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 font-mono text-xs">
                            {{ $role->guard_name }}
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-500 max-w-xs">
                            <div class="flex flex-wrap gap-1">
                                @forelse($role->permissions->take(5) as $perm)
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-600">
                                        {{ $perm->name }}
                                    </span>
                                @empty
                                    <span class="text-slate-400 italic text-xs">Tidak ada permission</span>
                                @endforelse
                                @if($role->permissions->count() > 5)
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-600">
                                        +{{ $role->permissions->count() - 5 }} lagi
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                {{ $role->permissions->count() }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-sm text-slate-500">
                            Belum ada role yang dikonfigurasi. Jalankan seeder terlebih dahulu.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Permissions by Group --}}
    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Daftar Permission (dikelompokkan)</h3>
        </div>
        <div class="p-6 space-y-6">
            @forelse ($permissions as $group => $perms)
            <div>
                <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-3 border-b border-slate-100 pb-2">
                    {{ ucfirst($group) }}
                </h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($perms as $permission)
                        <span class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-700/10">
                            {{ $permission->name }}
                        </span>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="py-8 text-center text-sm text-slate-500">
                <p>Belum ada permission yang terdaftar.</p>
                <p class="mt-1 text-xs text-slate-400">Jalankan <code class="bg-slate-100 px-1 py-0.5 rounded">php artisan db:seed --class=RoleAndPermissionSeeder</code></p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Add Role Modal --}}
    <div x-show="showAddRoleModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click.outside="showAddRoleModal = false" class="w-full max-w-md bg-white rounded-xl shadow-2xl ring-1 ring-slate-900/10 p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Tambah Role Baru</h3>
                <form action="{{ route('sistem.role-permission.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Role</label>
                        <input type="text" name="name" x-model="newRoleName" placeholder="Contoh: Proctor" class="block w-full rounded-md border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm" required>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showAddRoleModal = false" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Simpan Role
                        </button>
                    </div>
                </form>
            </div>
            <div class="fixed inset-0 bg-slate-900/50 -z-10"></div>
        </div>
    </div>
</div>
@endsection