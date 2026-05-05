<?php

namespace App\Http\Controllers\Kansor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kansor\UserIndexRequest;
use App\Services\Kansor\KansorLocalRepository;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    public function index(UserIndexRequest $request, KansorLocalRepository $KansorLocalRepository): View
    {
        $result = $KansorLocalRepository->users(auth()->user());

        return view('pos-kantin.users.index', [
            'summary' => $result['summary'],
            'users' => $result['items'],
        ]);
    }
}


