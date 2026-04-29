<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Services\PosKantin\PosKantinLocalRepository;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(PosKantinLocalRepository $posKantinLocalRepository): View
    {
        return view('pos-kantin.reports.index', [
            'generatedAt' => now(),
            'summary' => $posKantinLocalRepository->dashboardSummary(auth()->user()),
        ]);
    }
}
