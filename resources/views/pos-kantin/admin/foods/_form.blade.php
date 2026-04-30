<div class="row">
    <div class="col-md-6">
        <x-form.select name="supplier_id" label="Pemasok" required>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $food->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
        </x-form.select>
    </div>
    <div class="col-md-6">
        <x-form.input
            name="name"
            label="Nama makanan"
            :value="$food->name ?? ''"
            required
        />
    </div>
    <div class="col-md-4">
        <x-form.input
            name="unit"
            label="Satuan"
            :value="$food->unit ?? ''"
            required
        />
    </div>
    <div class="col-md-4">
        <x-form.money
            name="default_price"
            label="Harga default"
            :value="$food->default_price ?? ''"
        />
    </div>
    <div class="col-md-4">
        <x-form.select name="active" label="Status aktif">
                <option value="1" @selected((string) old('active', isset($food) ? (int) $food->active : 1) === '1')>Aktif</option>
                <option value="0" @selected((string) old('active', isset($food) ? (int) $food->active : 1) === '0')>Nonaktif</option>
        </x-form.select>
    </div>
</div>
