@php($editing = isset($userModel))

<div class="row">
    <div class="col-md-6">
        <x-form.input
            name="name"
            label="Nama"
            :value="$userModel->name ?? ''"
            required
        />
    </div>
    <div class="col-md-6">
        <x-form.input
            name="email"
            label="Email"
            type="email"
            :value="$userModel->email ?? ''"
            required
        />
    </div>
    <div class="col-md-6">
        <x-form.select name="role" label="Peran" required>
                <option value="admin" @selected(old('role', $userModel->role ?? 'petugas') === 'admin')>Admin</option>
                <option value="petugas" @selected(old('role', $userModel->role ?? 'petugas') === 'petugas')>Petugas</option>
        </x-form.select>
    </div>
    <div class="col-md-6">
        <x-form.select name="active" label="Status aktif">
                <option value="1" @selected((string) old('active', isset($userModel) ? (int) $userModel->active : 1) === '1')>Aktif</option>
                <option value="0" @selected((string) old('active', isset($userModel) ? (int) $userModel->active : 1) === '0')>Nonaktif</option>
        </x-form.select>
    </div>
    <div class="col-md-6">
        <x-form.input
            name="password"
            :label="$editing ? 'Password baru' : 'Password'"
            type="password"
            :required="! $editing"
            help="Gunakan password kuat minimal 12 karakter."
        />
    </div>
    <div class="col-md-6">
        <x-form.input
            name="password_confirmation"
            label="Konfirmasi password"
            type="password"
            :required="! $editing"
        />
    </div>
</div>
