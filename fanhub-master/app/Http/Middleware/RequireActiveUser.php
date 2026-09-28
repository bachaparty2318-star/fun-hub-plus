<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user(), 401, 'Please sign in.');
        if (! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Your account is inactive.');
        }

        return $next($request);
    }
}
