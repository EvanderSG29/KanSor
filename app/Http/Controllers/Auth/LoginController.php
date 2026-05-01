<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OfflineLoginService;
use App\Services\Auth\PosKantinUserAuthenticator;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

use function Illuminate\Support\defer;

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

    protected ?string $failedLoginMessage = null;

    protected ?string $resolvedLoginMode = null;

    public function __construct(
        private OfflineLoginService $offlineLoginService,
        private PosKantinSyncService $posKantinSyncService,
        private PosKantinUserAuthenticator $posKantinUserAuthenticator,
    ) {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login', [
            'trustedAccounts' => $this->offlineLoginService->trustedAccounts(),
        ]);
    }

    protected function attemptLogin(Request $request): bool
    {
        $result = $this->posKantinUserAuthenticator->attempt(
            (string) $request->input('email'),
            (string) $request->input('password'),
        );

        if (($result['success'] ?? false) !== true || ! isset($result['user'])) {
            $this->failedLoginMessage = (string) ($result['message'] ?? __('auth.failed'));

            return false;
        }

        $this->resolvedLoginMode = (string) ($result['mode'] ?? 'offline');
        $this->guard()->login($result['user'], $request->boolean('remember'));

        return true;
    }

    protected function authenticated(Request $request, $user)
    {
        if ($this->resolvedLoginMode === 'online') {
            $syncService = $this->posKantinSyncService;

            defer(function () use ($syncService, $user): void {
                try {
                    $syncService->sync($user, 'login');
                } catch (Throwable $exception) {
                    Log::warning('Sinkronisasi POS Kantin setelah login gagal dijalankan.', [
                        'user_id' => $user->getKey(),
                        'remote_user_id' => $user->remote_user_id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
        }
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [$this->failedLoginMessage ?: trans('auth.failed')],
        ]);
    }
}
