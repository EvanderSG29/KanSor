<?php

namespace App\Http\Controllers\Kansor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kansor\SupplierIndexRequest;
use App\Services\Kansor\KansorLocalRepository;
use Illuminate\Contracts\View\View;

class SupplierController extends Controller
{
    public function index(SupplierIndexRequest $request, KansorLocalRepository $KansorLocalRepository): View
    {
        $filters = $request->filters();
        $result = $KansorLocalRepository->suppliers(auth()->user(), $filters);

        return view('kansor.suppliers.index', [
            'filters' => $filters,
            'suppliers' => $result['items'],
            'summary' => $result['summary'],
        ]);
    }
}

<<<<<<< HEAD:app/Http/Controllers/PosKantin/SupplierController.php
=======

>>>>>>> 6549984 (	modified:   .env.example):app/Http/Controllers/Kansor/SupplierController.php
