<?php

namespace App\Http\Controllers\PosKantin\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\Admin\StoreSupplierRequest;
use App\Http\Requests\PosKantin\Admin\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\PosKantin\PosKantinMutationDispatcher;
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

        return view('pos-kantin.admin.suppliers.index', [
            'filters' => $request->only(['active', 'search']),
            'suppliers' => $suppliers,
        ]);
    }

    public function create(): View
    {
        return view('pos-kantin.admin.suppliers.create');
    }

    public function store(StoreSupplierRequest $request, PosKantinMutationDispatcher $dispatcher): RedirectResponse
    {
        $supplier = Supplier::query()->create([
            ...$request->validated(),
            'active' => $request->boolean('active'),
        ]);

        $dispatcher->dispatch('createSupplier', [[
            'id' => $supplier->getKey(),
            'name' => $supplier->name,
            'contactInfo' => $supplier->contact_info,
            'percentageCut' => (float) $supplier->percentage_cut,
            'active' => $supplier->active,
        ]], [
            'entity' => 'supplier',
            'id' => $supplier->getKey(),
        ]);

        return redirect()
            ->route('pos-kantin.admin.suppliers.index')
            ->with('status', 'Pemasok berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('pos-kantin.admin.suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier, PosKantinMutationDispatcher $dispatcher): RedirectResponse
    {
        $supplier->fill([
            ...$request->validated(),
            'active' => $request->boolean('active'),
        ])->save();

        $dispatcher->dispatch('updateSupplier', [$supplier->getKey(), [
            'id' => $supplier->getKey(),
            'name' => $supplier->name,
            'contactInfo' => $supplier->contact_info,
            'percentageCut' => (float) $supplier->percentage_cut,
            'active' => $supplier->active,
        ]], [
            'entity' => 'supplier',
            'id' => $supplier->getKey(),
        ]);

        return redirect()
            ->route('pos-kantin.admin.suppliers.index')
            ->with('status', 'Pemasok berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier, PosKantinMutationDispatcher $dispatcher): RedirectResponse
    {
        $supplier->fill([
            'active' => false,
        ])->save();

        $dispatcher->dispatch('deleteSupplier', [$supplier->getKey()], [
            'entity' => 'supplier',
            'id' => $supplier->getKey(),
        ]);

        return redirect()
            ->route('pos-kantin.admin.suppliers.index')
            ->with('status', 'Pemasok berhasil dinonaktifkan.');
    }
}
