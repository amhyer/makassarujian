<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function superAdmin()
    {
        return view('dashboard.index', ['title' => 'Super Admin Overview']);
    }

    public function adminSekolah()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();
            
            if (!$tenantId) {
                abort(403, 'Tenant ID not found for this user.');
            }

            $metrics = $this->dashboardService->getSchoolAdminMetrics($tenantId, $userId);

            // API layer prep: if expecting JSON
            if (request()->wantsJson()) {
                return response()->json($metrics);
            }

            return view('dashboard.admin-sekolah', [
                'title' => 'School Admin Dashboard',
                'metrics' => $metrics
            ]);
        } catch (\Throwable $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return view('dashboard.error', ['message' => 'Terjadi kesalahan saat memuat dashboard.']);
        }
    }

    public function adminFkgg()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            if (!$tenantId) {
                abort(403, 'Tenant ID not found for this user.');
            }

            $metrics = $this->dashboardService->getFkkgAdminMetrics($tenantId, $userId);

            if (request()->wantsJson()) {
                return response()->json($metrics);
            }

            return view('dashboard.admin-fkgg', [
                'title' => 'FKKG Admin Dashboard',
                'metrics' => $metrics
            ]);
        } catch (\Throwable $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());
            return view('dashboard.error', ['message' => 'Terjadi kesalahan saat memuat dashboard.']);
        }
    }

    public function siswa()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            if (!$tenantId) {
                abort(403, 'Tenant ID not found for this user.');
            }

            $metrics = $this->dashboardService->getStudentMetrics($tenantId, $userId);

            if (request()->wantsJson()) {
                return response()->json($metrics);
            }

            return view('dashboard.siswa', [
                'title' => 'Siswa Dashboard',
                'metrics' => $metrics
            ]);
        } catch (\Throwable $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());
            return view('dashboard.error', ['message' => 'Terjadi kesalahan saat memuat dashboard.']);
        }
    }

    public function defaultDashboard()
    {
        return view('dashboard.index', ['title' => 'My Dashboard']);
    }
}
