<?php

namespace App\Http\Controllers\PosKantin;

use App\Exceptions\PosKantinException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\TransactionIndexRequest;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\View\View;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request, PosKantinClient $posKantinClient): View
    {
        $errorMessage = null;
        $filters = $request->filters();
        $transactions = [];
        $summary = [];
        $pagination = null;

        try {
            $result = $posKantinClient->listTransactions([
                ...$filters,
                'includeSummary' => true,
            ]);

            $transactions = $result['items'] ?? [];
            $summary = $result['summary'] ?? [];
            $pagination = $result['pagination'] ?? null;
        } catch (PosKantinException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('pos-kantin.transactions.index', [
            'errorMessage' => $errorMessage,
            'filters' => $filters,
            'pagination' => $pagination,
            'summary' => $summary,
            'transactions' => $transactions,
        ]);
    }
}
