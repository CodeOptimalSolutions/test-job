<?php

namespace DTApi\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use DTApi\Models\Application;
use Illuminate\Support\Facades\Validator;

/**
 * Class ApiAppAuth
 * @package DTApi\Http\Middleware
 */
class ApiAppAuth
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
            // check token validation
            $this->payloadIsValid(
                $payload = (array) JWT::decode($authToken, env('API_SECRET'), ['HS256'])
            );

            $app = Application::whereKey($payload['sub'])->firstOrFail();
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response('token_expired', 401);
        } catch (\Throwable $e) {
            return response('token_invalid', 401);
        } catch (\UnexpectedValueException $e) {
            return response('token_invalid', 401);
        }

        if (! $app->is_active) {
            return response('app_inactive', 403);
        }

        $request->merge(['__authenticatedApp' => $app]);

        return $next($request);
    }

    /**
     * @param $payload
     */
    private function payloadIsValid($payload)
    {
        $validator = Validator::make($payload, [
            'iss' => 'required|in:DTApi',
            'sub' => 'required',
        ]);

        if (! $validator->passes()) {
            throw new \InvalidArgumentException;
        }
    }
}
