<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to one or more roles, e.g. middleware('role:admin').
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        $allowed = array_map(fn (string $r) => UserRole::from($r), $roles);

        abort_unless(in_array($user->role, $allowed, true), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
