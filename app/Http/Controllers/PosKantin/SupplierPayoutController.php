<?php

namespace App\Http\Controllers\PosKantin;

use App\Exceptions\PosKantinException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\SupplierPayoutIndexRequest;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\View\View;

class SupplierPayoutController extends Controller
{
    public function index(SupplierPayoutIndexRequest $request, PosKantinClient $posKantinClient): View
    {
        $history = [];
        $outstanding = [];
        $summary = [];
        $errorMessage = null;

        try {
            $payouts = $posKantinClient->listSupplierPayouts();
            $history = $payouts['history'] ?? [];
            $outstanding = $payouts['outstanding'] ?? [];
            $summary = $payouts['summary'] ?? [];
        } catch (PosKantinException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('pos-kantin.supplier-payouts.index', [
            'errorMessage' => $errorMessage,
            'history' => $history,
            'outstanding' => $outstanding,
            'summary' => $summary,
        ]);
    }
}
