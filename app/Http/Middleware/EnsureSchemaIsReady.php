<?php

namespace App\Http\Middleware;

use App\Services\Setup\SchemaReadinessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchemaIsReady
{
    public function __construct(
        private SchemaReadinessService $schemaReadinessService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('setup.*') || ! $this->schemaReadinessService->shouldBlockApplication()) {
            return $next($request);
        }

        $status = $this->schemaReadinessService->status();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Database lokal belum siap. Jalankan migrasi sebelum menggunakan aplikasi.',
                'data' => $status,
            ], 503);
        }

        return response()->view('setup.schema-readiness', [
            'schemaReadiness' => $status,
            'blockedUrl' => $request->fullUrl(),
        ]);
    }
}
