<?php

namespace App\Http\Controllers\PosKantin;

use App\Exceptions\PosKantinException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\SupplierIndexRequest;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\View\View;

class SupplierController extends Controller
{
    public function index(SupplierIndexRequest $request, PosKantinClient $posKantinClient): View
    {
        $suppliers = [];
        $errorMessage = null;
        $filters = $request->filters();

        try {
            $suppliers = $posKantinClient->listSuppliers($filters);
        } catch (PosKantinException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('pos-kantin.suppliers.index', [
            'errorMessage' => $errorMessage,
            'filters' => $filters,
            'suppliers' => $suppliers,
            'summary' => [
                'count' => count($suppliers),
                'activeCount' => collect($suppliers)->where('isActive', true)->count(),
            ],
        ]);
    }
}
