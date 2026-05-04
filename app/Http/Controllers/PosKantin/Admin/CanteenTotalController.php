<?php

namespace App\Http\Controllers\PosKantin\Admin;

use App\Http\Controllers\Controller;
use App\Models\CanteenTotal;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CanteenTotalController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $month = $request->string('month')->toString();

        $canteenTotals = CanteenTotal::query()
            ->when($month !== '', fn ($query) => $query->whereBetween('date', [$month.'-01', date('Y-m-t', strtotime($month.'-01'))]))
            ->when($from !== '', fn ($query) => $query->whereDate('date', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        $sales = Sale::query()
            ->with('supplier')
            ->when($month !== '', fn ($query) => $query->whereBetween('date', [$month.'-01', date('Y-m-t', strtotime($month.'-01'))]))
            ->when($from !== '', fn ($query) => $query->whereDate('date', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('date', '<=', $to))
            ->get();

        $supplierSummary = $sales
            ->groupBy('supplier_id')
            ->map(function ($group): array {
                $first = $group->first();

                return [
                    'supplier' => $first?->supplier?->name ?? 'Tanpa pemasok',
                    'total_canteen' => $group->sum('total_canteen'),
                    'total_supplier' => $group->sum('total_supplier'),
                ];
            })
            ->sortByDesc('total_canteen')
            ->values();

        return view('kansor.admin.canteen-totals.index', [
            'canteenTotals' => $canteenTotals,
            'filters' => $request->only(['from', 'to', 'month']),
            'supplierSummary' => $supplierSummary,
            'grandTotal' => $sales->sum('total_canteen'),
        ]);
    }
}

