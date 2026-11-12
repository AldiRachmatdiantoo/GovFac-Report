<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Enums\UserRole;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian: ->middleware('role:admin') atau ->middleware('role:admin,government')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException;
        }

        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        abort_unless(in_array($role, $roles, true), 403, 'Peran Anda tidak diizinkan mengakses ini.');

        return $next($request);
    }
}
