<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LogImpersonationActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session('impersonating')) {
            $impersonatorId = session('impersonated_by');
            $impersonatedUserId = auth()->id();

            // 1. Audit Logging to Database
            $log = \App\Models\ImpersonationLog::where('impersonator_id', $impersonatorId)
                ->where('impersonated_user_id', $impersonatedUserId)
                ->whereNull('ended_at')
                ->latest()
                ->first();

            if ($log) {
                $activities = $log->activities ?? [];
                $activities[] = [
                    'time' => now()->toDateTimeString(),
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                ];
                
                // Limit to last 50 activities to avoid massive rows
                $log->update(['activities' => array_slice($activities, -50)]);
            }

            // 2. Strict Restrictions
            // Block Billing Access
            if ($request->is('billing*') || $request->is('payments*') || $request->is('subscriptions*')) {
                abort(403, 'Akses area billing dilarang saat impersonation.');
            }

            // Block Delete Data (HTTP DELETE method)
            if ($request->isMethod('DELETE')) {
                abort(403, 'Penghapusan data dilarang saat impersonation.');
            }
        }

        return $next($request);
    }
}
