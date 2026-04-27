<?php

namespace App\Http\Controllers\PosKantin;

use App\Exceptions\PosKantinException;
use App\Http\Controllers\Controller;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(PosKantinClient $posKantinClient): View
    {
        $errorMessage = null;
        $summary = [];

        try {
            $summary = $posKantinClient->dashboardSummary();
        } catch (PosKantinException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('pos-kantin.reports.index', [
            'errorMessage' => $errorMessage,
            'generatedAt' => now(),
            'summary' => $summary,
        ]);
    }
}
