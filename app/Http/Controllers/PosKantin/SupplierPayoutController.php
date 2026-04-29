<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\SupplierPayoutIndexRequest;
use App\Services\PosKantin\PosKantinLocalRepository;
use Illuminate\Contracts\View\View;

class SupplierPayoutController extends Controller
{
    public function index(SupplierPayoutIndexRequest $request, PosKantinLocalRepository $posKantinLocalRepository): View
    {
        $payouts = $posKantinLocalRepository->supplierPayouts(auth()->user());

        return view('pos-kantin.supplier-payouts.index', [
            'history' => $payouts['history'],
            'outstanding' => $payouts['outstanding'],
            'summary' => $payouts['summary'],
        ]);
    }
}
