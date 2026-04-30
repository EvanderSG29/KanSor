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
use App\Services\Audit\AuditLogger;
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
            'sale' => $sale->load([
                'supplier',
                'user',
                'items.food',
                'supplierPaymentConfirmedBy',
                'canteenDepositConfirmedBy',
            ]),
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
        AuditLogger $auditLogger,
        SaleCalculationService $calculationService,
        CanteenTotalAggregationService $aggregationService,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $dispatchResult = [
            'status' => 'queued',
            'warning' => null,
        ];
        $beforeSnapshot = $this->auditSnapshotForSale($sale);

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

        $auditLogger->log($request, 'sale.updated', $sale, metadata: [
            'before' => $beforeSnapshot,
            'after' => $this->auditSnapshotForSale($sale->fresh('items')),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('pos-kantin.admin.sales.show', $sale)
                ->with('status', 'Transaksi berhasil dikoreksi.'),
            $dispatchResult,
        );
    }

    public function destroy(
        Request $request,
        Sale $sale,
        AuditLogger $auditLogger,
        CanteenTotalAggregationService $aggregationService,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $dispatchResult = [
            'status' => 'queued',
            'warning' => null,
        ];
        $auditSnapshot = $this->auditSnapshotForSale($sale);

        DB::transaction(function () use ($sale, $aggregationService, $transactionSyncService, &$dispatchResult): void {
            $date = $sale->date->format('Y-m-d');
            $saleItemIds = $sale->items()->pluck('id')->all();
            $sale->items()->get()->each->delete();
            $sale->delete();
            $aggregationService->recalculateForDate($date);
            $dispatchResult = $transactionSyncService->deleteSaleItems($saleItemIds);
        });

        $auditLogger->log($request, 'sale.deleted', $sale, metadata: $auditSnapshot);

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
        AuditLogger $auditLogger,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $validated = $request->validated();

        $sale->fill([
            'status_i' => Sale::STATUS_SUPPLIER_PAID,
            'supplier_paid_at' => $validated['paid_at'] ?? $sale->supplier_paid_at,
            'supplier_paid_amount' => $validated['paid_amount'] ?? $sale->supplier_paid_amount,
            'supplier_payment_note' => $validated['taken_note'] ?? $sale->supplier_payment_note,
            'supplier_payment_confirmed_by' => $request->user()->getKey(),
        ])->save();

        $dispatchResult = $transactionSyncService->syncSale($sale);

        $auditLogger->log($request, 'sale.supplier_payment_confirmed', $sale, metadata: [
            'status_i' => $sale->status_i,
            'supplier_paid_at' => $sale->supplier_paid_at?->format('Y-m-d'),
            'supplier_paid_amount' => $sale->supplier_paid_amount,
            'note_present' => filled($sale->supplier_payment_note),
        ]);

        return $this->withPosKantinDispatchNotice(
            back()->with('status', 'Status pembayaran pemasok berhasil dikonfirmasi.'),
            $dispatchResult,
        );
    }

    public function confirmCanteenDeposited(
        ConfirmCanteenDepositedRequest $request,
        Sale $sale,
        AuditLogger $auditLogger,
        CanteenTotalAggregationService $aggregationService,
        SaleTransactionSyncService $transactionSyncService,
    ): RedirectResponse {
        $validated = $request->validated();

        $sale->fill([
            'status_ii' => Sale::STATUS_CANTEEN_DEPOSITED,
            'canteen_deposited_at' => $validated['paid_at'],
            'canteen_deposited_amount' => $validated['paid_amount'],
            'canteen_deposit_note' => $validated['taken_note'] ?? $sale->canteen_deposit_note,
            'canteen_deposit_confirmed_by' => $request->user()->getKey(),
        ])->save();

        $aggregationService->recalculateForDate($sale->date->format('Y-m-d'));
        $dispatchResult = $transactionSyncService->syncSale($sale);

        $auditLogger->log($request, 'sale.canteen_deposit_confirmed', $sale, metadata: [
            'status_ii' => $sale->status_ii,
            'canteen_deposited_at' => $sale->canteen_deposited_at?->format('Y-m-d'),
            'canteen_deposited_amount' => $sale->canteen_deposited_amount,
            'note_present' => filled($sale->canteen_deposit_note),
        ]);

        return $this->withPosKantinDispatchNotice(
            back()->with('status', 'Setoran kantin berhasil dikonfirmasi.'),
            $dispatchResult,
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    private function auditSnapshotForSale(Sale $sale): array
    {
        return [
            'date' => $sale->date?->format('Y-m-d'),
            'supplier_id' => $sale->supplier_id,
            'user_id' => $sale->user_id,
            'item_count' => $sale->items()->count(),
            'total_supplier' => $sale->total_supplier,
            'total_canteen' => $sale->total_canteen,
            'status_i' => $sale->status_i,
            'status_ii' => $sale->status_ii,
        ];
    }
}
