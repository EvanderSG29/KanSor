<?php

namespace App\Http\Controllers\PosKantin;

use App\Exceptions\PosKantinException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\UserIndexRequest;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    public function index(UserIndexRequest $request, PosKantinClient $posKantinClient): View
    {
        $users = [];
        $errorMessage = null;

        try {
            $users = $posKantinClient->listUsers();
        } catch (PosKantinException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('pos-kantin.users.index', [
            'errorMessage' => $errorMessage,
            'summary' => [
                'count' => count($users),
                'activeCount' => collect($users)->where('status', 'aktif')->count(),
                'adminCount' => collect($users)->where('role', 'admin')->count(),
            ],
            'users' => $users,
        ]);
    }
}
