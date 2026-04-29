<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles all super admin pages that were previously using Route::view()
 * without any data. Each method passes safe default data so the blade
 * templates don't throw undefined variable errors.
 */
class SuperAdminPageController extends Controller
{
    // ─── UJIAN GLOBAL ────────────────────────────────────────────────────

    public function distribusi(): View
    {
        return view('pages.ujian.distribusi', [
            'distributions' => collect([]),
            'stats' => [
                'total'   => 0,
                'active'  => 0,
                'draft'   => 0,
                'schools' => 0,
            ],
        ]);
    }

    public function template(): View
    {
        return view('pages.ujian.template', [
            'templates' => collect([]),
        ]);
    }

    // ─── MONITORING ──────────────────────────────────────────────────────

    public function ujianBerlangsung(): View
    {
        return view('pages.monitoring.ujian-berlangsung', [
            'sessions' => collect([]),
            'stats' => [
                'active_sessions'    => 0,
                'total_participants' => 0,
                'cheat_alerts'       => 0,
                'active_schools'     => 0,
            ],
        ]);
    }

    public function aktivitasSiswa(): View
    {
        return view('pages.monitoring.aktivitas-siswa', [
            'logs' => collect([]),
            'stats' => [
                'total'   => 0,
                'cheats'  => 0,
                'offline' => 0,
                'submits' => 0,
            ],
        ]);
    }

    public function statusServer(): View
    {
        return view('pages.monitoring.status-server', [
            'metrics' => [
                'cpu'             => 0,
                'memory'          => 0,
                'queue_jobs'      => 0,
                'redis_connected' => false,
                'redis_memory'    => '—',
            ],
            'services' => [
                ['name' => 'Web Server (Nginx)',  'status' => 'up', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'Database (MySQL)',    'status' => 'up', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'Cache (Redis)',       'status' => 'up', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'Queue Worker',        'status' => 'up', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'WebSocket (Reverb)',  'status' => 'up', 'latency' => '—', 'uptime' => '—'],
                ['name' => 'Storage (S3)',        'status' => 'up', 'latency' => '—', 'uptime' => '—'],
            ],
            'errors' => [],
        ]);
    }

    // ─── USER MANAGEMENT ─────────────────────────────────────────────────

    public function adminSekolah(): View
    {
        return view('pages.user-management.admin-sekolah', [
            'admins' => collect([]),
            'stats' => [
                'total'        => 0,
                'active'       => 0,
                'logged_today' => 0,
                'inactive'     => 0,
            ],
        ]);
    }

    public function adminFkgg(): View
    {
        return view('pages.user-management.admin-fkgg', [
            'admins' => collect([]),
            'stats' => [
                'total'      => 0,
                'active'     => 0,
                'fkgg_count' => 0,
                'inactive'   => 0,
            ],
        ]);
    }

    // ─── SISTEM ──────────────────────────────────────────────────────────

    public function konfigurasi(): View
    {
        return view('pages.sistem.konfigurasi', [
            'config' => [
                'platform_name'        => config('app.name', 'Makassar Ujian'),
                'support_email'        => '',
                'timezone'             => 'Asia/Makassar',
                'locale'               => 'id',
                'trial_days'           => 14,
                'trial_reminder_days'  => 3,
                'auto_expire_trial'    => true,
                'max_participants'     => 500,
                'max_tab_switch'       => 3,
                'default_shuffle'      => true,
                'default_anti_cheat'   => true,
                'maintenance_mode'     => false,
                'notify_new_tenant'    => true,
                'notify_payment'       => true,
                'notify_trial_expiring'=> true,
            ],
        ]);
    }

    public function rolePermission(): View
    {
        return view('pages.sistem.role-permission', [
            'roles'       => collect([]),
            'permissions' => collect([]),
        ]);
    }

    public function auditLog(): View
    {
        return view('pages.sistem.audit-log', [
            'logs' => collect([]),
            'stats' => [
                'total'        => 0,
                'today'        => 0,
                'critical'     => 0,
                'unique_users' => 0,
            ],
        ]);
    }

    // ─── SUPPORT ─────────────────────────────────────────────────────────

    public function broadcast(): View
    {
        return view('pages.support.broadcast', [
            'broadcasts' => collect([]),
            'stats' => [
                'total'     => 0,
                'sent'      => 0,
                'scheduled' => 0,
                'failed'    => 0,
            ],
        ]);
    }

    public function tiket(): View
    {
        return view('pages.support.tiket', [
            'tickets' => collect([]),
            'stats' => [
                'total'       => 0,
                'open'        => 0,
                'in_progress' => 0,
                'resolved'    => 0,
            ],
        ]);
    }
}
