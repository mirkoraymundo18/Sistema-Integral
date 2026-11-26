<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureRecentActivity
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $now = now()->timestamp;
            $last = $request->session()->get('almacen_last_activity', $now);
            if (Auth::viaRemember() || $now - $last >= 1800) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Tu sesión terminó por 30 minutos de inactividad.');
            }
            $request->session()->put('almacen_last_activity', $now);
        }

        return $next($request);
    }
}
