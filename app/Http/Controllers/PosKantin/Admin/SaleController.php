<?php

namespace App\Http\Controllers\PosKantin\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\Admin\ConfirmCanteenDepositedRequest;
use App\Http\Requests\PosKantin\Admin\ConfirmSupplierPaidRequest;
use App\Http\Requests\PosKantin\UpdateSaleRequest;
use App\Models\Food;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PosKantin\CanteenTotalAggregationService;
use App\Services\PosKantin\SaleCalculationService;
use App\Services\PosKantin\SaleTransactionSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->with(['supplier', 'user'])
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('status_i'), fn ($query) => $query->where('status_i', $request->string('status_i')->toString()))
            ->when($request->filled('status_ii'), fn ($query) => $query->where('status_ii', $request->string('status_ii')->toString()))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('date', '>=', $request->string('from')->toString()))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('date', '<=', $request->string('to')->toString()))
            ->orderByDesc('date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('pos-kantin.admin.sales.index', [
            'filters' => $request->only(['supplier_id', 'status_i', 'status_ii', 'from', 'to']),
            'sales' => $sales,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Sale $sale): View
    {
        return view('pos-kantin.admin.sales.show', [
            'sale' => $sale->load(['supplier', 'user', 'items.food']),
        ]);
    }

    public function edit(Sale $sale): View
    {
        $sale->load('items.food');

        return view('pos-kantin.admin.sales.edit', [
            'sale' => $sale,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'foods' => Food::query()
                ->with('supplier')
                ->where(function ($query) use ($sale): void {
                    $query->where(function ($builder): void {
                        $builder->active()->whereHas('supplier', fn ($supplierQuery) => $supplierQuery->active());
                    })->orWhereIn('id', $sale->items->pluck('food_id')->all());
                })
                ->orderBy('name')
                ->get(),
            'additionalUsers' => User::query()->active()->petugas()->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateSaleRequest $request,
        Sale $sale,
        SaleCalculationService $calculationService,
        CanteenTotalAggregationService $aggregationService,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $dispatchResult = [
            'status' => 'queued',
            'warning' => null,
        ];

        DB::transaction(function () use ($request, $sale, $calculationService, $aggregationService, $transactionSyncService, &$dispatchResult): void {
            $supplier = Supplier::query()->findOrFail($request->integer('supplier_id'));
            $originalDate = $sale->date->format('Y-m-d');
            $originalItemIds = $sale->items()->pluck('id')->all();

            $sale->fill([
                'date' => $request->validated()['date'],
                'supplier_id' => $supplier->getKey(),
                'additional_users' => $request->validated()['additional_users'] ?? [],
            ])->save();

            $calculationService->syncItems($sale, $supplier, $request->validated()['items']);
            $aggregationService->recalculateForDate($originalDate);
            $aggregationService->recalculateForDate($sale->date->format('Y-m-d'));
            $deletedItemIds = array_values(array_diff($originalItemIds, $sale->items()->pluck('id')->all()));
            $dispatchResult = $transactionSyncService->syncSale($sale, $deletedItemIds);
        });

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('pos-kantin.admin.sales.show', $sale)
                ->with('status', 'Transaksi berhasil dikoreksi.'),
            $dispatchResult,
        );
    }

    public function destroy(
        Sale $sale,
        CanteenTotalAggregationService $aggregationService,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $dispatchResult = [
            'status' => 'queued',
            'warning' => null,
        ];

        DB::transaction(function () use ($sale, $aggregationService, $transactionSyncService, &$dispatchResult): void {
            $date = $sale->date->format('Y-m-d');
            $saleItemIds = $sale->items()->pluck('id')->all();
            $sale->items()->get()->each->delete();
            $sale->delete();
            $aggregationService->recalculateForDate($date);
            $dispatchResult = $transactionSyncService->deleteSaleItems($saleItemIds);
        });

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('pos-kantin.admin.sales.index')
                ->with('status', 'Transaksi berhasil dihapus dari operasional aktif.'),
            $dispatchResult,
        );
    }

    public function confirmSupplierPaid(
        ConfirmSupplierPaidRequest $request,
        Sale $sale,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $sale->fill([
            'status_i' => Sale::STATUS_SUPPLIER_PAID,
            'taken_note' => $request->validated()['taken_note'] ?? $sale->taken_note,
            'paid_at' => $request->validated()['paid_at'] ?? $sale->paid_at,
            'paid_amount' => $request->validated()['paid_amount'] ?? $sale->paid_amount,
        ])->save();

        $dispatchResult = $transactionSyncService->syncSale($sale);

        return $this->withPosKantinDispatchNotice(
            back()->with('status', 'Status pembayaran pemasok berhasil dikonfirmasi.'),
            $dispatchResult,
        );
    }

    public function confirmCanteenDeposited(
        ConfirmCanteenDepositedRequest $request,
        Sale $sale,
        CanteenTotalAggregationService $aggregationService,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $sale->fill([
            'status_ii' => Sale::STATUS_CANTEEN_DEPOSITED,
            'taken_note' => $request->validated()['taken_note'] ?? $sale->taken_note,
            'paid_at' => $request->validated()['paid_at'],
            'paid_amount' => $request->validated()['paid_amount'],
        ])->save();

        $aggregationService->recalculateForDate($sale->date->format('Y-m-d'));
        $dispatchResult = $transactionSyncService->syncSale($sale);

        return $this->withPosKantinDispatchNotice(
            back()->with('status', 'Setoran kantin berhasil dikonfirmasi.'),
            $dispatchResult,
        );
    }
}
