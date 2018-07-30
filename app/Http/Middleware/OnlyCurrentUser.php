<?php

namespace DTApi\Http\Middleware;

use Closure;
use Doctrine\DBAL\Types\IntegerType;

class OnlyCurrentUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->is('*/users/*') || $request->is('*/users') || $request->has('user_id')) {
            $user_id = $this->getResource($request);

            if($request->__authenticatedUser->user_type == 3 || $request->__authenticatedUser->user_type == 4) return $next($request);

            if($request->__authenticatedUser->id != $user_id)
            {
                return response('user_incorrect', 401);
            }
        }

        return $next($request);
    }

    /**
     * Get resource from route if it exists
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function getResource($request)
    {
        foreach ($request->route()->parameters() as $param)
        {
            return $param;
        }

        return null;
    }

}
