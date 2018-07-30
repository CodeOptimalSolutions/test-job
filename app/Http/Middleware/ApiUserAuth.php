<?php

namespace DTApi\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use DTApi\Models\User;
use DTApi\Models\Application;
use Illuminate\Support\Facades\Validator;

/**
 * Class ApiUserAuth
 * @package DTApi\Http\Middleware
 */
class ApiUserAuth
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
        $authToken = $request->bearerToken();

        try {
            $this->payloadIsValid(
                $payload = (array) JWT::decode($authToken, env('API_SECRET'), ['HS256'])
            );

            $app = Application::whereKey($payload['iss'])->firstOrFail();

            $user = User::whereEmail($payload['sub'])->with('userMeta')->firstOrFail();
        } catch (\Throwable $e) {
            return response('token_invalid', 401);
        }

        if($request->has('user_id') && $request->get('user_id') != $user->id)
        {
//            return response('user_incorrect', 401);
        }

        if (! $app->is_active) {
            return response('app_inactive', 403);
        }

        $request->merge(['__authenticatedApp' => $app]);

        $request->merge(['__authenticatedUser' => $user]);

        return $next($request);
    }

    /**
     * @param $payload
     */
    private function payloadIsValid($payload)
    {
        $validator = Validator::make($payload, [
            'iss' => 'required',
            'sub' => 'required',
            'jti' => 'required',
        ]);

        if (! $validator->passes()) {
            throw new \InvalidArgumentException;
        }
    }
}
