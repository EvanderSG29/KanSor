<?php

namespace App\Http\Controllers;

use App\Events\OpenTelescopeWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Native\Desktop\Facades\Window;

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

    public function controlWindow(Request $request, string $action): JsonResponse
    {
        abort_unless((bool) config('nativephp-internal.running'), 404);

        match ($action) {
            'minimize' => Window::minimize('main'),
            'maximize' => Window::maximize('main'),
            'reload' => Window::reload('main'),
            'close' => Window::close('main'),
            default => abort(404),
        };

        return response()->json([
            'success' => true,
            'action' => $action,
        ]);
    }
}
