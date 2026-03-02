<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCsvRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->session()->get('auth_user');
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        if ($role === null || ! in_array($role, $roles, true)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
