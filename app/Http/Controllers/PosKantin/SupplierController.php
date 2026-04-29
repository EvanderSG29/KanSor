<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\SupplierIndexRequest;
use App\Services\PosKantin\PosKantinLocalRepository;
use Illuminate\Contracts\View\View;

class SupplierController extends Controller
{
    public function index(SupplierIndexRequest $request, PosKantinLocalRepository $posKantinLocalRepository): View
    {
        $filters = $request->filters();
        $result = $posKantinLocalRepository->suppliers(auth()->user(), $filters);

        return view('pos-kantin.suppliers.index', [
            'filters' => $filters,
            'suppliers' => $result['items'],
            'summary' => $result['summary'],
        ]);
    }
}
