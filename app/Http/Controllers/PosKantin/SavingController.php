<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\SavingIndexRequest;
use App\Services\PosKantin\PosKantinLocalRepository;
use Illuminate\Contracts\View\View;

class SavingController extends Controller
{
    public function index(SavingIndexRequest $request, PosKantinLocalRepository $posKantinLocalRepository): View
    {
        $result = $posKantinLocalRepository->savings(auth()->user());

        return view('kansor.savings.index', [
            'savings' => $result['items'],
            'summary' => $result['summary'],
        ]);
    }
}

