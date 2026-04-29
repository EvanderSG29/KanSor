<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\StoreSaleRequest;
use App\Http\Requests\PosKantin\UpdateSaleRequest;
use App\Models\Food;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PosKantin\CanteenTotalAggregationService;
use App\Services\PosKantin\PosKantinMutationDispatcher;
use App\Services\PosKantin\SaleCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $sales = Sale::query()
            ->with(['supplier', 'user', 'items.food'])
            ->when($user->isPetugas(), fn ($query) => $query->where('user_id', $user->getKey()))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('date', '>=', $request->string('from')->toString()))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('date', '<=', $request->string('to')->toString()))
            ->orderByDesc('date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('pos-kantin.sales.index', [
            'filters' => $request->only(['supplier_id', 'from', 'to']),
            'sales' => $sales,
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Sale::class);

        return view('pos-kantin.sales.create', $this->formData(new Sale([
            'date' => now('Asia/Jakarta')->toDateString(),
            'additional_users' => [],
        ])));
    }

    public function store(
        StoreSaleRequest $request,
        SaleCalculationService $calculationService,
        CanteenTotalAggregationService $aggregationService,
        PosKantinMutationDispatcher $dispatcher,
    ): RedirectResponse {
        $this->authorize('create', Sale::class);

        $sale = DB::transaction(function () use ($request, $calculationService, $aggregationService, $dispatcher): Sale {
            $supplier = Supplier::query()->findOrFail($request->integer('supplier_id'));
            $sale = Sale::query()->create([
                'date' => $request->validated()['date'],
                'supplier_id' => $supplier->getKey(),
                'user_id' => auth()->id(),
                'additional_users' => $request->validated()['additional_users'] ?? [],
                'status_i' => Sale::STATUS_PENDING,
                'status_ii' => Sale::STATUS_PENDING,
                'taken_note' => null,
                'paid_at' => null,
                'paid_amount' => null,
            ]);

            $calculationService->syncItems($sale, $supplier, $request->validated()['items']);
            $aggregationService->recalculateForDate($sale->date->format('Y-m-d'));
            $dispatcher->dispatch('createTransaction', [$this->salePayload($sale->fresh(['supplier', 'user', 'items.food']))], [
                'entity' => 'sale',
                'id' => $sale->getKey(),
            ]);

            return $sale;
        });

        return redirect()
            ->route('pos-kantin.sales.show', $sale)
            ->with('status', 'Transaksi berhasil disimpan.');
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);

        return view('pos-kantin.sales.show', [
            'sale' => $sale->load(['supplier', 'user', 'items.food']),
        ]);
    }

    public function edit(Sale $sale): View
    {
        $this->authorize('update', $sale);

        return view('pos-kantin.sales.edit', $this->formData($sale->load('items.food')));
    }

    public function update(
        UpdateSaleRequest $request,
        Sale $sale,
        SaleCalculationService $calculationService,
        CanteenTotalAggregationService $aggregationService,
        PosKantinMutationDispatcher $dispatcher,
    ): RedirectResponse {
        $this->authorize('update', $sale);

        DB::transaction(function () use ($request, $sale, $calculationService, $aggregationService, $dispatcher): void {
            $supplier = Supplier::query()->findOrFail($request->integer('supplier_id'));
            $originalDate = $sale->date->format('Y-m-d');

            $sale->fill([
                'date' => $request->validated()['date'],
                'supplier_id' => $supplier->getKey(),
                'additional_users' => $request->validated()['additional_users'] ?? [],
            ])->save();

            $calculationService->syncItems($sale, $supplier, $request->validated()['items']);
            $aggregationService->recalculateForDate($originalDate);
            $aggregationService->recalculateForDate($sale->date->format('Y-m-d'));

            $dispatcher->dispatch('updateTransaction', [$sale->getKey(), $this->salePayload($sale->fresh(['supplier', 'user', 'items.food']))], [
                'entity' => 'sale',
                'id' => $sale->getKey(),
            ]);
        });

        return redirect()
            ->route('pos-kantin.sales.show', $sale)
            ->with('status', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(
        Sale $sale,
        CanteenTotalAggregationService $aggregationService,
        PosKantinMutationDispatcher $dispatcher,
    ): RedirectResponse {
        $this->authorize('delete', $sale);

        DB::transaction(function () use ($sale, $aggregationService, $dispatcher): void {
            $date = $sale->date->format('Y-m-d');
            $sale->items()->get()->each->delete();
            $sale->delete();
            $aggregationService->recalculateForDate($date);
            $dispatcher->dispatch('deleteTransaction', [$sale->getKey()], [
                'entity' => 'sale',
                'id' => $sale->getKey(),
            ]);
        });

        return redirect()
            ->route('pos-kantin.sales.index')
            ->with('status', 'Transaksi berhasil dibatalkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Sale $sale): array
    {
        $currentFoodIds = $sale->relationLoaded('items')
            ? $sale->items->pluck('food_id')->filter()->all()
            : [];

        return [
            'sale' => $sale,
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
            'foods' => Food::query()
                ->with('supplier')
                ->where(function ($query) use ($currentFoodIds): void {
                    $query->where(function ($builder): void {
                        $builder->active()->whereHas('supplier', fn ($supplierQuery) => $supplierQuery->active());
                    });

                    if ($currentFoodIds !== []) {
                        $query->orWhereIn('id', $currentFoodIds);
                    }
                })
                ->orderBy('name')
                ->get(),
            'additionalUsers' => User::query()
                ->active()
                ->petugas()
                ->whereKeyNot(auth()->id())
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function salePayload(Sale $sale): array
    {
        return [
            'id' => $sale->getKey(),
            'date' => $sale->date->format('Y-m-d'),
            'supplierId' => $sale->supplier_id,
            'supplierName' => $sale->supplier?->name,
            'userId' => $sale->user_id,
            'userName' => $sale->user?->name,
            'additionalUsers' => $sale->additional_users ?? [],
            'totalSupplier' => $sale->total_supplier,
            'totalCanteen' => $sale->total_canteen,
            'statusI' => $sale->status_i,
            'statusII' => $sale->status_ii,
            'takenNote' => $sale->taken_note,
            'paidAt' => optional($sale->paid_at)->format('Y-m-d'),
            'paidAmount' => $sale->paid_amount,
            'items' => $sale->items->map(fn ($item): array => [
                'id' => $item->getKey(),
                'foodId' => $item->food_id,
                'foodName' => $item->food?->name,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'leftover' => $item->leftover,
                'pricePerUnit' => $item->price_per_unit,
                'totalItem' => $item->total_item,
                'cutAmount' => $item->cut_amount,
            ])->all(),
        ];
    }
}
