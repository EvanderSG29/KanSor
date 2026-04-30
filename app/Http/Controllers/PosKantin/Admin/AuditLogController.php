<?php

namespace App\Http\Controllers\PosKantin\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $auditLogs = AuditLog::query()
            ->with('actor')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pos-kantin.admin.audit-logs.index', [
            'actions' => AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'auditLogs' => $auditLogs,
            'filters' => $request->only(['action']),
        ]);
    }
}
