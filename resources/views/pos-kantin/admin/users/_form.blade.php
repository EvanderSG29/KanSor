@php($editing = isset($userModel))

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $userModel->name ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $userModel->email ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Peran</label>
            <select name="role" class="form-control" required>
                <option value="admin" @selected(old('role', $userModel->role ?? 'petugas') === 'admin')>Admin</option>
                <option value="petugas" @selected(old('role', $userModel->role ?? 'petugas') === 'petugas')>Petugas</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Status aktif</label>
            <select name="active" class="form-control">
                <option value="1" @selected((string) old('active', isset($userModel) ? (int) $userModel->active : 1) === '1')>Aktif</option>
                <option value="0" @selected((string) old('active', isset($userModel) ? (int) $userModel->active : 1) === '0')>Nonaktif</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ $editing ? 'Password baru' : 'Password' }}</label>
            <input type="password" name="password" class="form-control" {{ $editing ? '' : 'required' }}>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Konfirmasi password</label>
            <input type="password" name="password_confirmation" class="form-control" {{ $editing ? '' : 'required' }}>
        </div>
    </div>
</div>
