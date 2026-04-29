@php
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
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" name="date" class="form-control" value="{{ old('date', optional($sale->date ?? null)?->format('Y-m-d') ?? $sale->date ?? now('Asia/Jakarta')->format('Y-m-d')) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Pemasok</label>
            <select name="supplier_id" class="form-control" data-sale-supplier required>
                <option value="">Pilih pemasok</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $sale->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Petugas tambahan</label>
            <select name="additional_users[]" class="form-control" multiple size="4">
                @php($selectedUsers = collect(old('additional_users', $sale->additional_users ?? []))->map(fn ($id) => (string) $id)->all())
                @foreach ($additionalUsers as $user)
                    <option value="{{ $user->id }}" @selected(in_array((string) $user->id, $selectedUsers, true))>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Item transaksi</h3>
        <button type="button" class="btn btn-sm btn-outline-primary" data-add-sale-row>Tambah baris</button>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Makanan</th>
                    <th>Satuan</th>
                    <th>Qty</th>
                    <th>Sisa</th>
                    <th>Harga</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody data-sale-items>
                @foreach ($formItems as $index => $item)
                    <tr>
                        <td>
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
                            <select name="items[{{ $index }}][food_id]" class="form-control" data-food-select required>
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
                        </td>
                        <td><input type="text" name="items[{{ $index }}][unit]" class="form-control" value="{{ $item['unit'] ?? '' }}" required></td>
                        <td><input type="number" min="1" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item['quantity'] ?? 1 }}" required></td>
                        <td><input type="number" min="0" name="items[{{ $index }}][leftover]" class="form-control" value="{{ $item['leftover'] ?? '' }}"></td>
                        <td><input type="text" name="items[{{ $index }}][price_per_unit]" class="form-control" value="{{ $item['price_per_unit'] ?? '' }}" required></td>
                        <td class="text-right"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>Hapus</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<template id="sale-item-row-template">
    <tr>
        <td>
            <input type="hidden" name="items[__INDEX__][id]" value="">
            <select name="items[__INDEX__][food_id]" class="form-control" data-food-select required>
                <option value="">Pilih makanan</option>
                @foreach ($foods as $food)
                    <option value="{{ $food->id }}" data-supplier-id="{{ $food->supplier_id }}" data-unit="{{ $food->unit }}" data-price="{{ $food->default_price }}">
                        {{ $food->name }} - {{ $food->supplier?->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="items[__INDEX__][unit]" class="form-control" required></td>
        <td><input type="number" min="1" name="items[__INDEX__][quantity]" class="form-control" value="1" required></td>
        <td><input type="number" min="0" name="items[__INDEX__][leftover]" class="form-control"></td>
        <td><input type="text" name="items[__INDEX__][price_per_unit]" class="form-control" required></td>
        <td class="text-right"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>Hapus</button></td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.querySelector('[data-sale-items]');
    const supplierSelect = document.querySelector('[data-sale-supplier]');
    const addButton = document.querySelector('[data-add-sale-row]');
    const template = document.getElementById('sale-item-row-template');

    if (!tbody || !supplierSelect || !addButton || !template) {
        return;
    }

    const rowCount = () => tbody.querySelectorAll('tr').length;

    const syncRowOptions = (row) => {
        const supplierId = supplierSelect.value;
        const foodSelect = row.querySelector('[data-food-select]');

        if (!foodSelect) {
            return;
        }

        Array.from(foodSelect.options).forEach((option) => {
            if (!option.dataset.supplierId) {
                option.hidden = false;
                return;
            }

            option.hidden = supplierId !== '' && option.dataset.supplierId !== supplierId;
        });
    };

    const syncFoodDefaults = (row) => {
        const foodSelect = row.querySelector('[data-food-select]');
        const selected = foodSelect?.selectedOptions?.[0];

        if (!selected) {
            return;
        }

        const unitInput = row.querySelector('input[name$="[unit]"]');
        const priceInput = row.querySelector('input[name$="[price_per_unit]"]');

        if (unitInput && unitInput.value.trim() === '') {
            unitInput.value = selected.dataset.unit || '';
        }

        if (priceInput && priceInput.value.trim() === '' && selected.dataset.price) {
            priceInput.value = selected.dataset.price;
        }
    };

    const attachRowEvents = (row) => {
        syncRowOptions(row);

        row.querySelector('[data-food-select]')?.addEventListener('change', () => syncFoodDefaults(row));
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => {
            if (rowCount() > 1) {
                row.remove();
            }
        });
    };

    supplierSelect.addEventListener('change', () => {
        tbody.querySelectorAll('tr').forEach((row) => syncRowOptions(row));
    });

    addButton.addEventListener('click', () => {
        const index = rowCount();
        const html = template.innerHTML.replaceAll('__INDEX__', index);
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;

        tbody.appendChild(row);
        attachRowEvents(row);
    });

    tbody.querySelectorAll('tr').forEach((row) => attachRowEvents(row));
});
</script>
@endpush
