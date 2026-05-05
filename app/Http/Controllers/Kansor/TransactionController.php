<?php

namespace App\Http\Controllers\Kansor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kansor\TransactionIndexRequest;
use App\Services\Kansor\KansorLocalRepository;
use Illuminate\Contracts\View\View;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request, KansorLocalRepository $KansorLocalRepository): View
    {
        $filters = $request->filters();
        $result = $KansorLocalRepository->transactions(auth()->user(), $filters);

        return view('kansor.transactions.index', [
            'filters' => $filters,
            'pagination' => $result['pagination'],
            'summary' => $result['summary'],
            'transactions' => $result['items'],
        ]);
    }
}

<<<<<<< HEAD:app/Http/Controllers/PosKantin/TransactionController.php
=======

>>>>>>> 6549984 (	modified:   .env.example):app/Http/Controllers/Kansor/TransactionController.php
