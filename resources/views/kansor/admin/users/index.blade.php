@extends('layouts.app')

@section('title', 'Kelola Pengguna')
@section('page_header')
    <x-pos.page-header title="Kelola Pengguna" subtitle="Pantau akun admin dan petugas dari satu tampilan yang konsisten.">
        <x-slot:actions>
            <a href="{{ route('kansor.admin.users.create') }}" class="btn btn-primary btn-sm">Tambah pengguna</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('kansor.partials.alerts')

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Daftar pengguna</h3>
    </div>
    <x-pos.filter-card :action="route('kansor.admin.users.index')" :reset-url="route('kansor.admin.users.index')" title="Filter pengguna" card-class="border-0 shadow-none mb-0">
            <div class="col-md-4">
                <x-form.select name="role" label="Peran">
                    <option value="">Semua</option>
                    <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                    <option value="petugas" @selected(($filters['role'] ?? '') === 'petugas')>Petugas</option>
                </x-form.select>
            </div>
            <div class="col-md-4">
                <x-form.select name="active" label="Status">
                    <option value="">Semua</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Aktif</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Nonaktif</option>
                </x-form.select>
            </div>
    </x-pos.filter-card>
    <div class="card-body table-responsive p-0">
        <x-pos.data-table :empty="$users->isEmpty()" :colspan="6" empty-title="Belum ada pengguna" empty-message="Tambahkan akun pertama untuk mulai mengelola akses admin dan petugas.">
            <x-slot:head>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $users->firstItem() + $loop->index }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><x-pos.status-badge :status="$user->role" context="role" /></td>
                    <td><x-pos.status-badge :status="$user->active ? 'aktif' : 'nonaktif'" context="active" /></td>
                    <td class="text-right">
                        <a href="{{ route('kansor.admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('kansor.admin.users.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan pengguna ini?')">Nonaktifkan</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
    <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection

