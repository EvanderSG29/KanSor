<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PosKantinAdminAuthenticator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    public function __construct(
        private PosKantinAdminAuthenticator $posKantinAdminAuthenticator,
    ) {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function attemptLogin(Request $request): bool
    {
        if ($this->guard()->attempt($this->credentials($request), $request->boolean('remember'))) {
            return true;
        }

        $user = $this->posKantinAdminAuthenticator->synchronizeAndResolve($request);

        if ($user === null) {
            return false;
        }

        $this->guard()->login($user, $request->boolean('remember'));

        return true;
    }
}
