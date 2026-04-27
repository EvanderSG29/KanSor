<?php

namespace App\Http\Controllers;

use App\Exceptions\PosKantinException;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(PosKantinClient $posKantinClient): View
    {
        $health = null;
        $summary = null;
        $backendError = null;

        try {
            $health = $posKantinClient->health();
            $summary = $posKantinClient->dashboardSummary();
        } catch (PosKantinException $exception) {
            $backendError = $exception->getMessage();
        }

        return view('home', [
            'backendError' => $backendError,
            'health' => $health,
            'summary' => $summary,
        ]);
    }
}
