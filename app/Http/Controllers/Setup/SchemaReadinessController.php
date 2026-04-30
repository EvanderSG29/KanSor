<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Services\Setup\SchemaReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchemaReadinessController extends Controller
{
    public function __construct(
        private SchemaReadinessService $schemaReadinessService,
    ) {}

    public function status(): JsonResponse
    {
        abort_unless(app()->environment('local'), 404);

        return response()->json([
            'data' => $this->schemaReadinessService->status(),
        ]);
    }

    public function runMigrations(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        $result = $this->schemaReadinessService->runPendingMigrations();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $result,
            ], $result['success'] ? 200 : 500);
        }

        return back()->with($result['success'] ? 'status' : 'error', $result['message']);
    }
}
