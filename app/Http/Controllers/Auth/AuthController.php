<?php

namespace DTApi\Http\Controllers\Auth;

use Validator;
use Firebase\JWT\JWT;
use DTApi\Models\User;
use Illuminate\Support\Str;
use DTApi\Models\Throttles;
use Illuminate\Http\Request;
use DTApi\Models\Application;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;
use DTApi\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as Req;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/api/v1/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
    }

    protected function maxLoginAttempts()
    {
        return property_exists($this, 'maxLoginAttempts') ? $this->maxLoginAttempts : 1;
    }

    protected function hasTooManyLoginAttempts(Request $request)
    {
        if (isset($request)) {
            if ($this->retriesLeft($request) <= 0) {
                $user = User::where('email', $request->email)->first();

                    $throttle = new Throttles();
                if ($user && User::findOrFail($user->id)) {
                    $throttle->user_id = $user->id;
                }
                else
                {
                    $throttle->user_id = 0;
                    $throttle->comment = $request->email;
                }
                    $throttle->ip = Req::ip();
                    $throttle->save();
            }
        }

        return app(RateLimiter::class)->tooManyAttempts(
            $this->getThrottleKey($request),
            $this->maxLoginAttempts(), $this->lockoutTime() / 60
        );
    }

    public function tooManyLogin(Request $request)
    {
        $data = $request->all();
        $user = User::where('email', $data['email'])->first();
        $throttle = new Throttles();
        if ($user && User::findOrFail($user->id)) {
            $throttle->user_id = $user->id;
        }
        else
        {
            $throttle->user_id = 0;
            $throttle->comment = $request->email;
        }
        $throttle->ip = $data['ip'];
        $throttle->save();
    }

    /**
     * @param Request $request
     * @return string
     */
    public function authorizeApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_key' => 'required|exists:applications,key,is_active,1',
        ]);

        if (!$validator->passes()) {
            return 'ffsdf';//redirect('/')->withMessage('Wrong credentials');
        }

        if (!Auth::validate($request->only(['email', 'password']))) {
            return response('invalid_credentials', 400);
        }

        $app = Application::whereKey($request->app_key)->first();

        $user = User::whereEmail($request->email)->first();

        $pivotData = ['Authorization_code' => $code = sha1($app->id . ':' . $user->id . str_random())];

        if ($app->users->contains($user)) {
            $app->users()->updateExistingPivot($user->id, $pivotData);
        } else {
            $app->users()->attach($user->id, $pivotData);
        }

        return response([
            'code' => $code
        ]);//redirect()->away($request->redirect_uri . '?code=' . $code);

    }

    public function loginAsUser(Request $request)
    {
        $cuser = $request->__authenticatedUser;
        if($cuser->user_type == env('SUPERADMIN_ROLE_ID')) {
            $app = Application::whereKey($request->get('app_key'))->first();

            $user = User::find($request->get('user_id'));

            $pivotData = ['Authorization_code' => $code = sha1($app->id . ':' . $user->id . str_random())];

            if ($app->users->contains($user)) {
                $app->users()->updateExistingPivot($user->id, $pivotData);
            } else {
                $app->users()->attach($user->id, $pivotData);
            }

            return response([
                'code' => $code
            ]);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function authenticateApp(Request $request)
    {
        $credentials = base64_decode(
            Str::substr($request->header('Authorization'), 6)
        );

        try {
            list($appKey, $appSecret) = explode(':', $credentials);

            $app = Application::whereKeyAndSecret($appKey, $appSecret)->firstOrFail();
        } catch (\Throwable $e) {
            return response('invalid_credentials', 400);
        }

        if (!$app->is_active) {
            return response('app_inactive', 403);
        }

        return response([
            'token_type'   => 'Bearer',
            'access_token' => $app->generateAuthToken(),
        ]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function authenticateUser(Request $request)
    {
//        $code = $request->json('code');
        $code = $request->get('code');

        $app = $request->__authenticatedApp;

        if (!$code || !$user = $app->users()->wherePivot('Authorization_code', $code)->with('userMeta')->first()) {
            return response('invalid_code', 400);
        }

        $app->users()->updateExistingPivot($user->id, ['Authorization_code' => null]);

        if($request->get('admin') == 'true') $user['by_admin'] = 'true';

        return response([
            'token_type'   => 'Bearer',
            'access_token' => $user->generateAuthToken($app),
            'user'         => $user,
        ]);
    }

    public function sessionMigration(Request $request)
    {
        $user = User::with('userMeta')->find($request->get('id'));

        $app = $request->__authenticatedApp;

        try {
            if($user) {
                $response = response([
                    'token_type'   => 'Bearer',
                    'access_token' => $user->generateAuthToken($app),
                    'user'         => $user,
                ]);

                return $response;
            }
        }
        catch (\FatalErrorException $e)
        {

        }
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        $throttles = $this->isUsingThrottlesLoginsTrait();

        if ($throttles && $lockedOut = $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->getCredentials($request);

        if (!Auth::validate($credentials)) {
            return redirect()->back()->withMessage('Неверный логин или пароль');
        }

//        if (Auth::guard($this->getGuard())->attempt($credentials, $request->has('remember'))) {
//            return $this->handleUserWasAuthenticated($request, $throttles);
//        }

        return $this->handleUserWasAuthenticated($request, $throttles);

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if ($throttles && !$lockedOut) {
            $this->incrementLoginAttempts($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'     => 'required|max:255',
            'email'    => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \DTApi\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }
}
