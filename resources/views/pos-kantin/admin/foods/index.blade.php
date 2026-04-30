@extends('layouts.app')

@section('title', 'Kelola Menu / Makanan')
@section('page_header')
    <x-pos.page-header title="Kelola Menu / Makanan" subtitle="Jaga katalog makanan tetap rapi, aktif, dan siap dipakai di transaksi harian.">
        <x-slot:actions>
            <a href="{{ route('pos-kantin.admin.foods.create') }}" class="btn btn-primary btn-sm">Tambah makanan</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Daftar makanan</h3>
    </div>
    <x-pos.filter-card :action="route('pos-kantin.admin.foods.index')" :reset-url="route('pos-kantin.admin.foods.index')" title="Filter menu" card-class="border-0 shadow-none mb-0">
            <div class="col-md-4">
                <x-form.select name="supplier_id" label="Pemasok">
                    <option value="">Semua</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </x-form.select>
            </div>
            <div class="col-md-4">
                <x-form.select name="active" label="Status">
                    <option value="">Semua</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Aktif</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Nonaktif</option>
                </x-form.select>
            </div>
            <div class="col-md-4">
                <x-form.input name="search" label="Cari nama" :value="$filters['search'] ?? ''" />
            </div>
    </x-pos.filter-card>
    <div class="card-body table-responsive p-0">
        <x-pos.data-table :empty="$foods->isEmpty()" :colspan="6" empty-title="Belum ada makanan" empty-message="Tambahkan menu aktif agar petugas bisa memilihnya saat input transaksi.">
            <x-slot:head>
                <tr>
                    <th>Pemasok</th>
                    <th>Nama makanan</th>
                    <th>Satuan</th>
                    <th>Harga default</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @foreach ($foods as $food)
                <tr>
                    <td>{{ $food->supplier?->name ?? '-' }}</td>
                    <td>{{ $food->name }}</td>
                    <td>{{ $food->unit }}</td>
                    <td>{{ $food->default_price !== null ? 'Rp '.number_format($food->default_price, 0, ',', '.') : '-' }}</td>
                    <td><x-pos.status-badge :status="$food->active ? 'aktif' : 'nonaktif'" context="active" /></td>
                    <td class="text-right">
                        <a href="{{ route('pos-kantin.admin.foods.edit', $food) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('pos-kantin.admin.foods.destroy', $food) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan makanan ini?')">Nonaktifkan</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
    <div class="card-footer">{{ $foods->links() }}</div>
</div>
@endsection
