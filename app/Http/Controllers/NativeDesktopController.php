<?php

namespace App\Http\Controllers;

use App\Events\OpenTelescopeWindow;
use Illuminate\Http\JsonResponse;

class NativeDesktopController extends Controller
{
    public function openTelescopeWindow(): JsonResponse
    {
        event(new OpenTelescopeWindow);

        return response()->json([
            'success' => true,
        ]);
    }
}
