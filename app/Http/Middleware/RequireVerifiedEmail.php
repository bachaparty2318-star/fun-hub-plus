<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireVerifiedEmail
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->hasVerifiedEmail(), 403, 'Verify your email address before using this feature.');

        return $next($request);
    }
}
