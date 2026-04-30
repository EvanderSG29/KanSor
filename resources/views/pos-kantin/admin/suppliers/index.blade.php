@extends('layouts.app')

@section('title', 'Kelola Pemasok')
@section('page_header')
    <x-pos.page-header title="Kelola Pemasok" subtitle="Atur pemasok aktif, kontak, dan potongan dengan struktur tampilan yang sama antar halaman admin.">
        <x-slot:actions>
            <a href="{{ route('pos-kantin.admin.suppliers.create') }}" class="btn btn-primary btn-sm">Tambah pemasok</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('pos-kantin.partials.alerts')

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Daftar pemasok</h3>
    </div>
    <x-pos.filter-card :action="route('pos-kantin.admin.suppliers.index')" :reset-url="route('pos-kantin.admin.suppliers.index')" title="Filter pemasok" card-class="border-0 shadow-none mb-0">
            <div class="col-md-4">
                <x-form.input name="search" label="Cari nama" :value="$filters['search'] ?? ''" />
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
        <x-pos.data-table :empty="$suppliers->isEmpty()" :colspan="6" empty-title="Belum ada pemasok" empty-message="Tambahkan pemasok agar menu dan transaksi bisa dikelola sesuai sumbernya.">
            <x-slot:head>
                <tr>
                    <th>Nama pemasok</th>
                    <th>Kontak</th>
                    <th>Potongan</th>
                    <th>Jumlah makanan</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @foreach ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->contact_info ?: '-' }}</td>
                    <td>{{ number_format((float) $supplier->percentage_cut, 2, ',', '.') }}%</td>
                    <td>{{ number_format($supplier->foods_count) }}</td>
                    <td><x-pos.status-badge :status="$supplier->active ? 'aktif' : 'nonaktif'" context="active" /></td>
                    <td class="text-right">
                        <a href="{{ route('pos-kantin.admin.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('pos-kantin.admin.suppliers.destroy', $supplier) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan pemasok ini?')">Nonaktifkan</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
    <div class="card-footer">{{ $suppliers->links() }}</div>
</div>
@endsection
