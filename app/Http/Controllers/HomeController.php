<?php

namespace App\Http\Controllers;

use App\Services\PosKantin\PosKantinLocalRepository;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(PosKantinLocalRepository $posKantinLocalRepository, PosKantinSyncService $posKantinSyncService): View
    {
        return view('home', [
            'summary' => $posKantinLocalRepository->dashboardSummary(auth()->user()),
            'syncStatus' => $posKantinSyncService->statusForUser(auth()->user()),
        ]);
    }
}
