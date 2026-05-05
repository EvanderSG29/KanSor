<?php

namespace App\Http\Controllers\Kansor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kansor\SavingIndexRequest;
use App\Services\Kansor\KansorLocalRepository;
use Illuminate\Contracts\View\View;

class SavingController extends Controller
{
    public function index(SavingIndexRequest $request, KansorLocalRepository $KansorLocalRepository): View
    {
        $result = $KansorLocalRepository->savings(auth()->user());

        return view('pos-kantin.savings.index', [
            'savings' => $result['items'],
            'summary' => $result['summary'],
        ]);
    }
}


