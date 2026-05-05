<?php

namespace App\Http\Controllers\Kansor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kansor\SupplierPayoutIndexRequest;
use App\Services\Kansor\KansorLocalRepository;
use Illuminate\Contracts\View\View;

class SupplierPayoutController extends Controller
{
    public function index(SupplierPayoutIndexRequest $request, KansorLocalRepository $KansorLocalRepository): View
    {
        $payouts = $KansorLocalRepository->supplierPayouts(auth()->user());

        return view('kansor.supplier-payouts.index', [
            'history' => $payouts['history'],
            'outstanding' => $payouts['outstanding'],
            'summary' => $payouts['summary'],
        ]);
    }
}

<<<<<<< HEAD:app/Http/Controllers/PosKantin/SupplierPayoutController.php
=======

>>>>>>> 6549984 (	modified:   .env.example):app/Http/Controllers/Kansor/SupplierPayoutController.php
