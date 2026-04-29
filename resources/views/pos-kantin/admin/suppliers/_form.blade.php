<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Nama pemasok</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Kontak</label>
            <input type="text" name="contact_info" class="form-control" value="{{ old('contact_info', $supplier->contact_info ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Persentase potongan</label>
            <input type="number" step="0.01" min="0" max="100" name="percentage_cut" class="form-control" value="{{ old('percentage_cut', isset($supplier) ? (float) $supplier->percentage_cut : '') }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Status aktif</label>
            <select name="active" class="form-control">
                <option value="1" @selected((string) old('active', isset($supplier) ? (int) $supplier->active : 1) === '1')>Aktif</option>
                <option value="0" @selected((string) old('active', isset($supplier) ? (int) $supplier->active : 1) === '0')>Nonaktif</option>
            </select>
        </div>
    </div>
</div>
