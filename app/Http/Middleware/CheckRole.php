<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!in_array($request->user()?->user_status, $roles)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
