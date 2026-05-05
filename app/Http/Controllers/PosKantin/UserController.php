<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\UserIndexRequest;
use App\Services\PosKantin\PosKantinLocalRepository;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    public function index(UserIndexRequest $request, PosKantinLocalRepository $posKantinLocalRepository): View
    {
        $result = $posKantinLocalRepository->users(auth()->user());

        return view('kansor.users.index', [
            'summary' => $result['summary'],
            'users' => $result['items'],
        ]);
    }
}

