<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, ?string $permission = null): Response
    {
        $user = $request->user();

        if ($user === null || !$user->hasModule($module)) {
            abort(403, 'You do not have access to this module.');
        }

        if ($permission !== null && !$user->hasModulePermission($module, $permission)) {
            abort(403, 'You do not have this operation permission.');
        }

        return $next($request);
    }
}
