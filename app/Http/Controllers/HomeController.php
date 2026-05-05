<?php

namespace App\Http\Controllers;

use App\Services\Kansor\KansorLocalRepository;
use App\Services\Kansor\KansorSyncService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(KansorLocalRepository $KansorLocalRepository, KansorSyncService $KansorSyncService): View
    {
        return view('home', [
            'summary' => $KansorLocalRepository->dashboardSummary(auth()->user()),
            'syncStatus' => $KansorSyncService->statusForUser(auth()->user()),
        ]);
    }
}


