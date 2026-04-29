<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\TransactionIndexRequest;
use App\Services\PosKantin\PosKantinLocalRepository;
use Illuminate\Contracts\View\View;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request, PosKantinLocalRepository $posKantinLocalRepository): View
    {
        $filters = $request->filters();
        $result = $posKantinLocalRepository->transactions(auth()->user(), $filters);

        return view('pos-kantin.transactions.index', [
            'filters' => $filters,
            'pagination' => $result['pagination'],
            'summary' => $result['summary'],
            'transactions' => $result['items'],
        ]);
    }
}
