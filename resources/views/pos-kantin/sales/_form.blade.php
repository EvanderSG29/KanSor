@php
    $selectedUsers = collect(old('additional_users', $sale->additional_users ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();

    $formItems = old('items', isset($sale) && $sale->relationLoaded('items') && $sale->items->isNotEmpty()
        ? $sale->items->map(fn ($item) => [
            'id' => $item->id,
            'food_id' => $item->food_id,
            'unit' => $item->unit,
            'quantity' => $item->quantity,
            'leftover' => $item->leftover,
            'price_per_unit' => $item->price_per_unit,
        ])->all()
        : [['food_id' => '', 'unit' => '', 'quantity' => 1, 'leftover' => '', 'price_per_unit' => '']]);

    $selectedSupplierId = (string) old('supplier_id', $sale->supplier_id ?? '');
    $selectedDate = old('date', optional($sale->date ?? null)?->format('Y-m-d') ?? $sale->date ?? now('Asia/Jakarta')->format('Y-m-d'));
@endphp

<div data-sale-form>
    <div class="row">
        <div class="col-lg-4">
            <div class="form-group">
                <label for="sale_date">Tanggal Transaksi</label>
                <input
                    id="sale_date"
                    type="date"
                    name="date"
                    class="form-control @error('date') is-invalid @enderror"
                    value="{{ $selectedDate }}"
                    required
                >
                @error('date')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-group">
                <label for="sale_supplier_id">Pemasok</label>
                <select
                    id="sale_supplier_id"
                    name="supplier_id"
                    class="form-control @error('supplier_id') is-invalid @enderror"
                    data-sale-supplier
                    required
                >
                    <option value="">Pilih pemasok</option>
                    @foreach ($suppliers as $supplier)
                        <option
                            value="{{ $supplier->id }}"
                            data-percentage-cut="{{ $supplier->percentage_cut }}"
                            @selected($selectedSupplierId === (string) $supplier->id)
                        >
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-group">
                <label for="sale_additional_users">Petugas Tambahan</label>
                <select
                    id="sale_additional_users"
                    name="additional_users[]"
                    class="form-control @error('additional_users') is-invalid @enderror"
                    multiple
                    size="4"
                >
                    @foreach ($additionalUsers as $user)
                        <option value="{{ $user->id }}" @selected(in_array((string) $user->id, $selectedUsers, true))>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Pilih bila transaksi dicatat bersama petugas lain.</small>
                @error('additional_users')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">Ringkasan sebelum simpan</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
                    <div class="border rounded h-100 p-3 bg-light">
                        <div class="text-muted small text-uppercase">Total Terjual</div>
                        <div class="h4 mb-0" data-sale-summary="gross">Rp 0</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
                    <div class="border rounded h-100 p-3 bg-light">
                        <div class="text-muted small text-uppercase">Total Pemasok</div>
                        <div class="h4 mb-0" data-sale-summary="supplier">Rp 0</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-3 mb-md-0">
                    <div class="border rounded h-100 p-3 bg-light">
                        <div class="text-muted small text-uppercase">Total Kantin</div>
                        <div class="h4 mb-0" data-sale-summary="canteen">Rp 0</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded h-100 p-3 bg-light">
                        <div class="text-muted small text-uppercase">Jumlah Item</div>
                        <div class="h4 mb-0" data-sale-summary="count">0 item</div>
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-0 mt-3">Ringkasan ini diperbarui otomatis berdasarkan item dan potongan pemasok yang dipilih.</p>
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
            <div class="mb-2 mb-lg-0">
                <h3 class="card-title mb-1">Item transaksi</h3>
                <p class="text-muted small mb-0">Isi makanan, jumlah, sisa, dan harga per item. Form tetap nyaman dipakai di tablet maupun ponsel.</p>
            </div>
            <button type="button" class="btn btn-primary" data-add-sale-row>Tambah Item</button>
        </div>
        <div class="card-body">
            <div class="mb-3">
                @error('items')
                    <div class="alert alert-danger mb-0">{{ $message }}</div>
                @enderror
            </div>

            <div data-sale-items>
                @foreach ($formItems as $index => $item)
                    <div class="card border shadow-sm mb-3" data-sale-item data-sale-row-index="{{ $index }}">
                        <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                            <div>
                                <strong data-sale-item-title>Item {{ $loop->iteration }}</strong>
                                <div class="text-muted small">Subtotal otomatis dihitung dari qty x harga.</div>
                            </div>
                            <div class="mt-2 mt-md-0">
                                <span class="badge badge-light border px-3 py-2" data-sale-item-subtotal>Rp 0</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">

                            <div class="form-row">
                                <div class="col-xl-5">
                                    <div class="form-group">
                                        <label for="sale_items_{{ $index }}_food_id">Makanan</label>
                                        <select
                                            id="sale_items_{{ $index }}_food_id"
                                            name="items[{{ $index }}][food_id]"
                                            class="form-control @error("items.$index.food_id") is-invalid @enderror"
                                            data-food-select
                                            required
                                        >
                                            <option value="">Pilih makanan</option>
                                            @foreach ($foods as $food)
                                                <option
                                                    value="{{ $food->id }}"
                                                    data-supplier-id="{{ $food->supplier_id }}"
                                                    data-unit="{{ $food->unit }}"
                                                    data-price="{{ $food->default_price }}"
                                                    @selected((string) ($item['food_id'] ?? '') === (string) $food->id)
                                                >
                                                    {{ $food->name }} - {{ $food->supplier?->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("items.$index.food_id")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-2">
                                    <div class="form-group">
                                        <label for="sale_items_{{ $index }}_unit">Satuan</label>
                                        <input
                                            id="sale_items_{{ $index }}_unit"
                                            type="text"
                                            name="items[{{ $index }}][unit]"
                                            class="form-control @error("items.$index.unit") is-invalid @enderror"
                                            value="{{ $item['unit'] ?? '' }}"
                                            placeholder="pcs"
                                            required
                                        >
                                        @error("items.$index.unit")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-2">
                                    <div class="form-group">
                                        <label for="sale_items_{{ $index }}_quantity">Qty</label>
                                        <input
                                            id="sale_items_{{ $index }}_quantity"
                                            type="number"
                                            min="1"
                                            step="1"
                                            inputmode="numeric"
                                            name="items[{{ $index }}][quantity]"
                                            class="form-control @error("items.$index.quantity") is-invalid @enderror"
                                            value="{{ $item['quantity'] ?? 1 }}"
                                            data-sale-quantity
                                            required
                                        >
                                        @error("items.$index.quantity")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-1">
                                    <div class="form-group">
                                        <label for="sale_items_{{ $index }}_leftover">Sisa</label>
                                        <input
                                            id="sale_items_{{ $index }}_leftover"
                                            type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            name="items[{{ $index }}][leftover]"
                                            class="form-control @error("items.$index.leftover") is-invalid @enderror"
                                            value="{{ $item['leftover'] ?? '' }}"
                                            data-sale-leftover
                                        >
                                        @error("items.$index.leftover")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-2">
                                    <div class="form-group">
                                        <label for="sale_items_{{ $index }}_price_per_unit">Harga</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input
                                                id="sale_items_{{ $index }}_price_per_unit"
                                                type="text"
                                                inputmode="numeric"
                                                name="items[{{ $index }}][price_per_unit]"
                                                class="form-control @error("items.$index.price_per_unit") is-invalid @enderror"
                                                value="{{ $item['price_per_unit'] ?? '' }}"
                                                placeholder="0"
                                                data-sale-price
                                                required
                                            >
                                        </div>
                                        @error("items.$index.price_per_unit")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                <div class="text-muted small mb-2 mb-md-0" data-sale-item-note>
                                    Lengkapi qty dan harga untuk melihat subtotal.
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Hapus Item</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<template id="sale-item-row-template">
    <div class="card border shadow-sm mb-3" data-sale-item data-sale-row-index="__INDEX__">
        <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
                <strong data-sale-item-title>Item</strong>
                <div class="text-muted small">Subtotal otomatis dihitung dari qty x harga.</div>
            </div>
            <div class="mt-2 mt-md-0">
                <span class="badge badge-light border px-3 py-2" data-sale-item-subtotal>Rp 0</span>
            </div>
        </div>
        <div class="card-body">
            <input type="hidden" name="items[__INDEX__][id]" value="">

            <div class="form-row">
                <div class="col-xl-5">
                    <div class="form-group">
                        <label for="sale_items___INDEX___food_id">Makanan</label>
                        <select
                            id="sale_items___INDEX___food_id"
                            name="items[__INDEX__][food_id]"
                            class="form-control"
                            data-food-select
                            required
                        >
                            <option value="">Pilih makanan</option>
                            @foreach ($foods as $food)
                                <option
                                    value="{{ $food->id }}"
                                    data-supplier-id="{{ $food->supplier_id }}"
                                    data-unit="{{ $food->unit }}"
                                    data-price="{{ $food->default_price }}"
                                >
                                    {{ $food->name }} - {{ $food->supplier?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-2">
                    <div class="form-group">
                        <label for="sale_items___INDEX___unit">Satuan</label>
                        <input
                            id="sale_items___INDEX___unit"
                            type="text"
                            name="items[__INDEX__][unit]"
                            class="form-control"
                            placeholder="pcs"
                            required
                        >
                    </div>
                </div>
                <div class="col-sm-6 col-xl-2">
                    <div class="form-group">
                        <label for="sale_items___INDEX___quantity">Qty</label>
                        <input
                            id="sale_items___INDEX___quantity"
                            type="number"
                            min="1"
                            step="1"
                            inputmode="numeric"
                            name="items[__INDEX__][quantity]"
                            class="form-control"
                            value="1"
                            data-sale-quantity
                            required
                        >
                    </div>
                </div>
                <div class="col-sm-6 col-xl-1">
                    <div class="form-group">
                        <label for="sale_items___INDEX___leftover">Sisa</label>
                        <input
                            id="sale_items___INDEX___leftover"
                            type="number"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            name="items[__INDEX__][leftover]"
                            class="form-control"
                            data-sale-leftover
                        >
                    </div>
                </div>
                <div class="col-sm-6 col-xl-2">
                    <div class="form-group">
                        <label for="sale_items___INDEX___price_per_unit">Harga</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input
                                id="sale_items___INDEX___price_per_unit"
                                type="text"
                                inputmode="numeric"
                                name="items[__INDEX__][price_per_unit]"
                                class="form-control"
                                placeholder="0"
                                data-sale-price
                                required
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <div class="text-muted small mb-2 mb-md-0" data-sale-item-note>
                    Lengkapi qty dan harga untuk melihat subtotal.
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Hapus Item</button>
            </div>
        </div>
    </div>
</template>
