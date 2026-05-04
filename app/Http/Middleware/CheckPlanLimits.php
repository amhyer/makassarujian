<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Exam;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = Auth::user();
        
        // Super Admin tidak terkena limitasi paket
        if ($user && $user->role === 'Super Admin') {
            return $next($request);
        }

        $tenantId = $user->tenant_id;

        // Ambil langganan yang aktif dan belum kedaluwarsa
        $subscription = Subscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'trial'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (!$subscription || !$subscription->plan) {
            abort(403, 'Tidak ada paket berlangganan yang aktif. Silakan hubungi Administrator sistem.');
        }

        $plan = $subscription->plan;

        // Validasi Limit Siswa
        if ($feature === 'students' && $plan->student_limit !== null) {
            $currentStudents = User::where('tenant_id', $tenantId)->where('role', 'Student')->count();
            if ($currentStudents >= $plan->student_limit) {
                abort(403, 'Batas kuota siswa untuk paket Anda telah tercapai (' . $plan->student_limit . ' siswa). Silakan upgrade paket Anda.');
            }
        }

        // Validasi Limit Ujian
        if ($feature === 'exams' && $plan->exam_limit !== null) {
            $currentExams = Exam::where('tenant_id', $tenantId)->count();
            if ($currentExams >= $plan->exam_limit) {
                abort(403, 'Batas pembuatan ujian untuk paket Anda telah tercapai (' . $plan->exam_limit . ' ujian). Silakan upgrade paket Anda.');
            }
        }

        return $next($request);
    }
}