<?php

namespace App\Http\Controllers\PosKantin;

use App\Exceptions\PosKantinException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\SavingIndexRequest;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\View\View;

class SavingController extends Controller
{
    public function index(SavingIndexRequest $request, PosKantinClient $posKantinClient): View
    {
        $savings = [];
        $errorMessage = null;

        try {
            $savings = $posKantinClient->listSavings();
        } catch (PosKantinException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('pos-kantin.savings.index', [
            'errorMessage' => $errorMessage,
            'savings' => $savings,
            'summary' => [
                'count' => count($savings),
                'depositAmount' => collect($savings)->sum('depositAmount'),
                'changeBalance' => collect($savings)->sum('changeBalance'),
            ],
        ]);
    }
}
