<div class="row">
    <div class="col-md-6">
        <x-form.input
            name="name"
            label="Nama pemasok"
            :value="$supplier->name ?? ''"
            required
        />
    </div>
    <div class="col-md-6">
        <x-form.input
            name="contact_info"
            label="Kontak"
            :value="$supplier->contact_info ?? ''"
        />
    </div>
    <div class="col-md-6">
        <x-form.input
            name="percentage_cut"
            label="Persentase potongan"
            type="number"
            :value="isset($supplier) ? (float) $supplier->percentage_cut : ''"
            required
            step="0.01"
            min="0"
            max="100"
        />
    </div>
    <div class="col-md-6">
        <x-form.select name="active" label="Status aktif">
                <option value="1" @selected((string) old('active', isset($supplier) ? (int) $supplier->active : 1) === '1')>Aktif</option>
                <option value="0" @selected((string) old('active', isset($supplier) ? (int) $supplier->active : 1) === '0')>Nonaktif</option>
        </x-form.select>
    </div>
</div>
