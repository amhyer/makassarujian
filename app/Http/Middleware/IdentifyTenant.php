<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->tenant_id) {
            app()->instance('currentTenant', Auth::user()->tenant_id);
            app()->instance('tenant_id', Auth::user()->tenant_id);
        }

        return $next($request);
    }
}
