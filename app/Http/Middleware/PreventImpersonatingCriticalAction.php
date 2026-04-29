<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventImpersonatingCriticalAction
{
    /**
     * Blokir aksi kritis (activate, expire, delete, billing) selama impersonation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session('impersonating') === true) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Aksi ini tidak diizinkan saat sedang melakukan impersonation.',
                ], 403);
            }

            return redirect()->back()->with(
                'error',
                '🚫 Aksi ini tidak diizinkan saat Anda sedang login sebagai admin sekolah lain. Hentikan sesi impersonation terlebih dahulu.'
            );
        }

        return $next($request);
    }
}
