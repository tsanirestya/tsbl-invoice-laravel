<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && $user->password_change_required) {
            // Allow logout and the change-password routes through
            if (!$request->routeIs('password.change.form', 'password.change', 'logout')) {
                return redirect()->route('password.change.form')
                    ->with('warning', 'Anda harus mengganti password sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
