<?php

namespace App\Http\Controllers\PosKantin\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\Admin\StoreFoodRequest;
use App\Http\Requests\PosKantin\Admin\UpdateFoodRequest;
use App\Models\Food;
use App\Models\Supplier;
use App\Services\PosKantin\FoodSyncPayloadFactory;
use App\Services\PosKantin\PosKantinMutationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function index(Request $request): View
    {
        $foods = Food::query()
            ->with('supplier')
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('kansor.admin.foods.index', [
            'filters' => $request->only(['supplier_id', 'active', 'search']),
            'foods' => $foods,
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('kansor.admin.foods.create', [
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreFoodRequest $request,
        PosKantinMutationDispatcher $dispatcher,
        FoodSyncPayloadFactory $payloadFactory,
    ): RedirectResponse {
        $food = Food::query()->create($request->validated());

        $dispatchResult = $dispatcher->dispatch('saveFood', [
            $payloadFactory->make($food),
        ], [
            'entity' => 'food',
            'id' => $food->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('kansor.admin.foods.index')
                ->with('status', 'Makanan berhasil ditambahkan.'),
            $dispatchResult,
        );
    }

    public function edit(Food $food): View
    {
        return view('kansor.admin.foods.edit', [
            'food' => $food,
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateFoodRequest $request,
        Food $food,
        PosKantinMutationDispatcher $dispatcher,
        FoodSyncPayloadFactory $payloadFactory,
    ): RedirectResponse {
        $food->fill($request->validated())->save();

        $dispatchResult = $dispatcher->dispatch('saveFood', [
            $payloadFactory->make($food),
        ], [
            'entity' => 'food',
            'id' => $food->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('kansor.admin.foods.index')
                ->with('status', 'Makanan berhasil diperbarui.'),
            $dispatchResult,
        );
    }

    public function destroy(
        Food $food,
        PosKantinMutationDispatcher $dispatcher,
        FoodSyncPayloadFactory $payloadFactory,
    ): RedirectResponse {
        $food->fill([
            'active' => false,
        ])->save();

        $dispatchResult = $dispatcher->dispatch('saveFood', [
            $payloadFactory->make($food),
        ], [
            'entity' => 'food',
            'id' => $food->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('kansor.admin.foods.index')
                ->with('status', 'Makanan berhasil dinonaktifkan.'),
            $dispatchResult,
        );
    }
}

