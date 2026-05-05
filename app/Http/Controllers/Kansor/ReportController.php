<?php

namespace App\Http\Controllers\Kansor;

use App\Http\Controllers\Controller;
use App\Services\Kansor\KansorLocalRepository;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(KansorLocalRepository $KansorLocalRepository): View
    {
        return view('pos-kantin.reports.index', [
            'generatedAt' => now(),
            'summary' => $KansorLocalRepository->dashboardSummary(auth()->user()),
        ]);
    }
}


