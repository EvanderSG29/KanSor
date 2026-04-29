@extends('layouts.app')

@section('title', 'CRUD Pengguna POS')

@section('content')
@include('pos-kantin.partials.alerts')

<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar pengguna</h3>
        <a href="{{ route('pos-kantin.admin.users.create') }}" class="btn btn-primary btn-sm">Tambah pengguna</a>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-4">
                <label>Peran</label>
                <select name="role" class="form-control">
                    <option value="">Semua</option>
                    <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                    <option value="petugas" @selected(($filters['role'] ?? '') === 'petugas')>Petugas</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>Status</label>
                <select name="active" class="form-control">
                    <option value="">Semua</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Aktif</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-outline-primary mr-2">Filter</button>
                <a href="{{ route('pos-kantin.admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $users->firstItem() + $loop->index }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge badge-info">{{ strtoupper($user->role) }}</span></td>
                        <td>
                            <span class="badge {{ $user->active ? 'badge-success' : 'badge-secondary' }}">
                                {{ $user->active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('pos-kantin.admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('pos-kantin.admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan pengguna ini?')">Nonaktifkan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada pengguna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection
