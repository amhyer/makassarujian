<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attempt;
use Symfony\Component\HttpFoundation\Response;

class CheckExamSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->has('attempt_id')) {
            $attemptId = $request->input('attempt_id');
            $attempt = Attempt::where('id', $attemptId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->first();

            if ($attempt && $attempt->session_id && $attempt->session_id !== session()->getId()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Sesi ujian Anda telah aktif di perangkat lain. Silakan gunakan perangkat tersebut atau hubungi pengawas.',
                        'error_code' => 'MULTI_DEVICE_DETECTED'
                    ], 403);
                }

                Auth::logout();
                return redirect()->route('login')->withErrors(['session' => 'Sesi ujian aktif di perangkat lain.']);
            }
        }

        return $next($request);
    }
}
