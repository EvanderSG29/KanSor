@extends('layouts.app')

@section('title', 'Kelola Menu / Makanan')

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar makanan</h3>
        <a href="{{ route('pos-kantin.admin.foods.create') }}" class="btn btn-primary btn-sm">Tambah makanan</a>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-4">
                <label>Pemasok</label>
                <select name="supplier_id" class="form-control">
                    <option value="">Semua</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
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
            <div class="col-md-4">
                <label>Cari nama</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-12 mt-3">
                <button class="btn btn-outline-primary mr-2">Filter</button>
                <a href="{{ route('pos-kantin.admin.foods.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Pemasok</th>
                    <th>Nama makanan</th>
                    <th>Satuan</th>
                    <th>Harga default</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($foods as $food)
                    <tr>
                        <td>{{ $food->supplier?->name ?? '-' }}</td>
                        <td>{{ $food->name }}</td>
                        <td>{{ $food->unit }}</td>
                        <td>{{ $food->default_price !== null ? 'Rp '.number_format($food->default_price, 0, ',', '.') : '-' }}</td>
                        <td><span class="badge {{ $food->active ? 'badge-success' : 'badge-secondary' }}">{{ $food->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right">
                            <a href="{{ route('pos-kantin.admin.foods.edit', $food) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('pos-kantin.admin.foods.destroy', $food) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan makanan ini?')">Nonaktifkan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada makanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $foods->links() }}</div>
</div>
@endsection
