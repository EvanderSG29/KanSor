<?php

namespace App\Http\Controllers;

use App\Events\OpenTelescopeWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NativeDesktopController extends Controller
{
    public function openTelescopeWindow(Request $request): JsonResponse
    {
        abort_unless(
            app()->environment('local') && $request->user()?->isAdmin() && $request->user()?->isActiveUser(),
            403,
        );

        event(new OpenTelescopeWindow);

        return response()->json([
            'success' => true,
        ]);
    }
}
