<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Pemasok</label>
            <select name="supplier_id" class="form-control" required>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $food->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Nama makanan</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $food->name ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Satuan</label>
            <input type="text" name="unit" class="form-control" value="{{ old('unit', $food->unit ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Harga default</label>
            <input type="text" name="default_price" class="form-control" value="{{ old('default_price', $food->default_price ?? '') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Status aktif</label>
            <select name="active" class="form-control">
                <option value="1" @selected((string) old('active', isset($food) ? (int) $food->active : 1) === '1')>Aktif</option>
                <option value="0" @selected((string) old('active', isset($food) ? (int) $food->active : 1) === '0')>Nonaktif</option>
            </select>
        </div>
    </div>
</div>
