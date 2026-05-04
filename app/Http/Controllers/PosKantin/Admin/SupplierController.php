<?php

namespace App\Http\Controllers\PosKantin\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\Admin\StoreSupplierRequest;
use App\Http\Requests\PosKantin\Admin\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\PosKantin\PosKantinMutationDispatcher;
use App\Services\PosKantin\SupplierSyncPayloadFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withCount('foods')
            ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('kansor.admin.suppliers.index', [
            'filters' => $request->only(['active', 'search']),
            'suppliers' => $suppliers,
        ]);
    }

    public function create(): View
    {
        return view('kansor.admin.suppliers.create');
    }

    public function store(
        StoreSupplierRequest $request,
        PosKantinMutationDispatcher $dispatcher,
        SupplierSyncPayloadFactory $supplierSyncPayloadFactory,
    ): RedirectResponse {
        $supplier = Supplier::query()->create([
            ...$request->validated(),
            'active' => $request->boolean('active'),
        ]);

        $dispatchResult = $dispatcher->dispatch('saveSupplier', [$supplierSyncPayloadFactory->make($supplier)], [
            'entity' => 'supplier',
            'id' => $supplier->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('kansor.admin.suppliers.index')
                ->with('status', 'Pemasok berhasil ditambahkan.'),
            $dispatchResult,
        );
    }

    public function edit(Supplier $supplier): View
    {
        return view('kansor.admin.suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier,
        PosKantinMutationDispatcher $dispatcher,
        SupplierSyncPayloadFactory $supplierSyncPayloadFactory,
    ): RedirectResponse {
        $supplier->fill([
            ...$request->validated(),
            'active' => $request->boolean('active'),
        ])->save();

        $dispatchResult = $dispatcher->dispatch('saveSupplier', [$supplierSyncPayloadFactory->make($supplier)], [
            'entity' => 'supplier',
            'id' => $supplier->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('kansor.admin.suppliers.index')
                ->with('status', 'Pemasok berhasil diperbarui.'),
            $dispatchResult,
        );
    }

    public function destroy(
        Supplier $supplier,
        PosKantinMutationDispatcher $dispatcher,
        SupplierSyncPayloadFactory $supplierSyncPayloadFactory,
    ): RedirectResponse {
        $supplier->fill([
            'active' => false,
        ])->save();

        $dispatchResult = $dispatcher->dispatch('saveSupplier', [$supplierSyncPayloadFactory->make($supplier)], [
            'entity' => 'supplier',
            'id' => $supplier->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('kansor.admin.suppliers.index')
                ->with('status', 'Pemasok berhasil dinonaktifkan.'),
            $dispatchResult,
        );
    }
}

