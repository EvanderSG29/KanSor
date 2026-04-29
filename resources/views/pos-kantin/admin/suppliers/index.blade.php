@extends('layouts.app')

@section('title', 'CRUD Pemasok POS')

@section('content')
@include('pos-kantin.partials.alerts')

<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar pemasok</h3>
        <a href="{{ route('pos-kantin.admin.suppliers.create') }}" class="btn btn-primary btn-sm">Tambah pemasok</a>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-4">
                <label>Cari nama</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}">
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
                <a href="{{ route('pos-kantin.admin.suppliers.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama pemasok</th>
                    <th>Kontak</th>
                    <th>Potongan</th>
                    <th>Jumlah makanan</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->contact_info ?: '-' }}</td>
                        <td>{{ number_format((float) $supplier->percentage_cut, 2, ',', '.') }}%</td>
                        <td>{{ number_format($supplier->foods_count) }}</td>
                        <td><span class="badge {{ $supplier->active ? 'badge-success' : 'badge-secondary' }}">{{ $supplier->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right">
                            <a href="{{ route('pos-kantin.admin.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('pos-kantin.admin.suppliers.destroy', $supplier) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan pemasok ini?')">Nonaktifkan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pemasok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $suppliers->links() }}</div>
</div>
@endsection
