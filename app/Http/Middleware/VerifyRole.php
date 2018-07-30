<?php

namespace DTApi\Http\Middleware;

use Closure;

class VerifyRole
{

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param int|string $role
     * @return view
     */
    public function handle($request, Closure $next, $role)
    {
        if (isset($request->__authenticatedUser) && $request->__authenticatedUser->is($role)) {
            return $next($request);
        }

        return isset($request->__authenticatedUser) ?
            response('permission_denied', 403) :
            response('token_invalid', 401);
    }
}