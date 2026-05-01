<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Attempt;
use App\Models\CheatLog;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Announcement;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminPageController extends Controller
{
    // ─── MONITORING ──────────────────────────────────────────────────────

    public function ujianBerlangsung(): View
    {
        $sessions = Attempt::withoutGlobalScope('tenant')
            ->with(['user', 'exam', 'tenant'])
            ->where('status', 'ongoing')
            ->latest('started_at')
            ->paginate(10);

        $totalParticipants = Attempt::withoutGlobalScope('tenant')->count();
        $activeSessions = Attempt::withoutGlobalScope('tenant')->where('status', 'ongoing')->count();
        
        $activeSchools = Attempt::withoutGlobalScope('tenant')
            ->where('status', 'ongoing')
            ->distinct('tenant_id')
            ->count('tenant_id');

        $cheatAlerts = CheatLog::whereDate('created_at', today())->count();

        return view('pages.monitoring.ujian-berlangsung', [
            'sessions' => $sessions,
            'stats' => [
                'active_sessions'    => $activeSessions,
                'total_participants' => $totalParticipants,
                'cheat_alerts'       => $cheatAlerts,
                'active_schools'     => $activeSchools,
            ],
        ]);
    }

    public function aktivitasSiswa(): View
    {
        $logs = CheatLog::with(['attempt' => function($q) {
                $q->withoutGlobalScope('tenant')->with(['user', 'exam', 'tenant']);
            }])
            ->latest()
            ->paginate(15);

        $totalCheatsToday = CheatLog::whereDate('created_at', today())->count();
        $totalSubmits = Attempt::withoutGlobalScope('tenant')->where('status', 'completed')->whereDate('updated_at', today())->count();

        return view('pages.monitoring.aktivitas-siswa', [
            'logs' => $logs,
            'stats' => [
                'total'   => CheatLog::count(),
                'cheats'  => $totalCheatsToday,
                'offline' => 0, // feature placeholder
                'submits' => $totalSubmits,
            ],
        ]);
    }

    public function statusServer(): View
    {
        // Pengecekan Database
        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Exception $e) {}

        // Pengecekan Redis
        $redisConnected = false;
        $redisMemory = '—';
        try {
            Redis::connection()->ping();
            $redisConnected = true;
            $info = Redis::connection()->info();
            $redisMemory = isset($info['Memory']['used_memory_human']) ? $info['Memory']['used_memory_human'] : '—';
        } catch (\Exception $e) {}

        // Pengecekan Queue
        $queueJobs = 0;
        try {
            $queueJobs = Queue::size();
        } catch (\Exception $e) {}

        // CPU & Memory (Linux only, fallback to 0 for Windows local dev)
        $cpu = 0;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && isset($load[0])) {
                $cpu = $load[0];
            }
        }

        return view('pages.monitoring.status-server', [
            'metrics' => [
                'cpu'             => $cpu,
                'memory'          => 0, // Complex to get cross-platform in PHP natively
                'queue_jobs'      => $queueJobs,
                'redis_connected' => $redisConnected,
                'redis_memory'    => $redisMemory,
            ],
            'services' => [
                ['name' => 'Database (PostgreSQL/MySQL)', 'status' => $dbConnected ? 'up' : 'down', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'Cache (Redis)',               'status' => $redisConnected ? 'up' : 'down', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'Queue Worker',                'status' => 'up', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'WebSocket (Reverb)',          'status' => 'up', 'latency' => '—', 'uptime' => '—'],
            ],
            'errors' => [],
        ]);
    }

    // ─── USER MANAGEMENT ─────────────────────────────────────────────────

    public function adminSekolah(): View
    {
        $admins = User::role('School Admin')->with('tenant')->latest()->paginate(15);
        
        $total = User::role('School Admin')->count();
        // Since we don't have a direct 'status' or 'logged_today' column right now,
        // we'll just populate total and leave the others as placeholders or derived.
        
        return view('pages.user-management.admin-sekolah', [
            'admins' => $admins,
            'stats' => [
                'total'        => $total,
                'active'       => $total, // Mocked for now
                'logged_today' => 0,
                'inactive'     => 0,
            ],
        ]);
    }

    public function adminFkgg(): View
    {
        try {
            $admins = User::role('FKKG Admin')->with('tenant')->latest()->paginate(15);
            $total = User::role('FKKG Admin')->count();
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            $admins = collect([]);
            $total = 0;
        }

        return view('pages.user-management.admin-fkgg', [
            'admins' => $admins,
            'stats' => [
                'total'      => $total,
                'active'     => $total,
                'new_this_week' => 0,
            ],
        ]);
    }

    // ─── SISTEM ──────────────────────────────────────────────────────────

    public function konfigurasi(Request $request): View
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'platform_name' => 'required|string|max:255',
                'default_trial_days' => 'required|integer|min:0',
                'max_concurrent_users' => 'required|integer|min:0',
            ]);

            foreach ($data as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
            
            // To pass a success message to the view, we use session flash
            return redirect()->route('superadmin.konfigurasi')->with('success', 'Konfigurasi berhasil disimpan!');
        }

        // Default values if not set
        $settings = Setting::all()->keyBy('key');
        
        $config = [
            'platform_name' => $settings->get('platform_name')->value ?? 'Makassar Ujian',
            'default_trial_days' => $settings->get('default_trial_days')->value ?? 14,
            'max_concurrent_users' => $settings->get('max_concurrent_users')->value ?? 1000,
        ];

        return view('pages.sistem.konfigurasi', [
            'config' => $config
        ]);
    }

    public function rolePermission(): View
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Group by the first part of the permission name, e.g., "users" from "users.create"
            $parts = explode('.', $permission->name);
            return $parts[0];
        });

        return view('pages.sistem.role-permission', [
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function auditLog(): View
    {
        $logs = AuditLog::with(['user', 'tenant'])
            ->latest()
            ->paginate(15);

        return view('pages.sistem.audit-log', [
            'logs' => $logs
        ]);
    }

    // ─── UJIAN GLOBAL ────────────────────────────────────────────────────

    public function template(): View
    {
        $templates = \App\Models\Exam::template()->latest()->paginate(15);
        $total = \App\Models\Exam::template()->count();

        return view('pages.ujian.template', [
            'templates' => $templates,
            'stats' => [
                'total'  => $total,
                'active' => $total, // Asumsi semua aktif
                'draft'  => 0,
            ],
        ]);
    }

    public function distribusi(): View
    {
        // Distribusi: Melacak sekolah mana saja yang meng-copy template
        $distributions = \App\Models\Exam::with(['tenant', 'originalTemplate'])
            ->whereNotNull('copied_from_id')
            ->latest()
            ->paginate(15);

        return view('pages.ujian.distribusi', [
            'distributions' => $distributions,
            'stats' => [
                'total_copied' => \App\Models\Exam::whereNotNull('copied_from_id')->count(),
                'active_exams' => 0,
            ],
        ]);
    }

    // ─── SUPPORT ─────────────────────────────────────────────────────────

    public function broadcast(): View
    {
        // Assuming Announcement model exists as per audit docs
        $broadcasts = Announcement::latest()->paginate(15);

        return view('pages.support.broadcast', [
            'broadcasts' => $broadcasts,
            'stats' => [
                'total'     => Announcement::count(),
                'sent'      => Announcement::where('status', 'sent')->count(),
                'scheduled' => Announcement::where('status', 'scheduled')->count(),
                'draft'     => Announcement::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function tiket(): View
    {
        // Assuming Ticket model exists as per audit docs
        $tickets = Ticket::with(['user', 'tenant'])
            ->withoutGlobalScope('tenant') // Super admin can see all tickets
            ->latest()
            ->paginate(15);

        return view('pages.support.tiket', [
            'tickets' => $tickets,
            'stats' => [
                'total'       => Ticket::withoutGlobalScope('tenant')->count(),
                'open'        => Ticket::withoutGlobalScope('tenant')->where('status', 'open')->count(),
                'in_progress' => Ticket::withoutGlobalScope('tenant')->where('status', 'in_progress')->count(),
                'resolved'    => Ticket::withoutGlobalScope('tenant')->where('status', 'resolved')->count(),
            ],
        ]);
    }
}
