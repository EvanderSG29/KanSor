@extends('layouts.app')

@section('title', 'Konfirmasi Admin')
@section('page_header')
    <x-pos.page-header title="Konfirmasi Admin" subtitle="Pantau transaksi yang menunggu tindak lanjut pembayaran pemasok dan setoran kantin.">
        <x-slot:actions>
            <a href="{{ route('kansor.sales.create') }}" class="btn btn-outline-primary btn-sm">Input transaksi</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('kansor.partials.alerts')
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title mb-0">Daftar transaksi untuk admin</h3></div>
    <x-pos.filter-card :action="route('kansor.admin.sales.index')" :reset-url="route('kansor.admin.sales.index')" title="Filter transaksi admin" card-class="border-0 shadow-none mb-0">
            <div class="col-md-3">
                <x-form.input name="from" label="Dari" type="date" :value="$filters['from'] ?? ''" />
            </div>
            <div class="col-md-3">
                <x-form.input name="to" label="Sampai" type="date" :value="$filters['to'] ?? ''" />
            </div>
            <div class="col-md-2">
                <x-form.select name="supplier_id" label="Pemasok">
                    <option value="">Semua</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </x-form.select>
            </div>
            <div class="col-md-2">
                <x-form.select name="status_i" label="Status Pembayaran Pemasok">
                    <option value="">Semua</option>
                    <option value="menunggu" @selected(($filters['status_i'] ?? '') === 'menunggu')>Menunggu</option>
                    <option value="dibayar" @selected(($filters['status_i'] ?? '') === 'dibayar')>Dibayar</option>
                </x-form.select>
            </div>
            <div class="col-md-2">
                <x-form.select name="status_ii" label="Status Setoran Kantin">
                    <option value="">Semua</option>
                    <option value="menunggu" @selected(($filters['status_ii'] ?? '') === 'menunggu')>Menunggu</option>
                    <option value="disetor" @selected(($filters['status_ii'] ?? '') === 'disetor')>Disetor</option>
                </x-form.select>
            </div>
    </x-pos.filter-card>
    <div class="card-body table-responsive p-0">
        <x-pos.data-table :empty="$sales->isEmpty()" :colspan="8" empty-title="Belum ada transaksi untuk admin" empty-message="Transaksi yang memerlukan tindak lanjut akan tampil di sini setelah petugas menyimpannya.">
            <x-slot:head>
                <tr>
                    <th>Tanggal</th>
                    <th>Pemasok</th>
                    <th>Petugas</th>
                    <th>Total pemasok</th>
                    <th>Total kantin</th>
                    <th>Status Pembayaran Pemasok</th>
                    <th>Status Setoran Kantin</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @foreach ($sales as $sale)
                <tr>
                    <td>{{ $sale->date->format('d/m/Y') }}</td>
                    <td>{{ $sale->supplier?->name ?? '-' }}</td>
                    <td>{{ $sale->user?->name ?? '-' }}</td>
                    <td>Rp {{ number_format($sale->total_supplier, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($sale->total_canteen, 0, ',', '.') }}</td>
                    <td><x-pos.status-badge :status="$sale->status_i" context="supplier-payment" /></td>
                    <td><x-pos.status-badge :status="$sale->status_ii" context="canteen-deposit" /></td>
                    <td class="text-right">
                        <a href="{{ route('kansor.admin.sales.show', $sale) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                        <a href="{{ route('kansor.admin.sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary">Koreksi</a>
                    </td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
    <div class="card-footer">{{ $sales->links() }}</div>
</div>
@endsection

