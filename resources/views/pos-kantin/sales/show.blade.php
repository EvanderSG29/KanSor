@extends('layouts.app')

@section('title', 'Detail Transaksi Harian')
@section('page_header')
    <x-pos.page-header title="Detail Transaksi Harian" subtitle="Lihat ringkasan transaksi, status pembayaran, dan rincian item dalam satu tampilan.">
        <x-slot:actions>
            @can('update', $sale)
                <a href="{{ route('pos-kantin.sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary mr-2">Edit</a>
            @endcan
            @can('delete', $sale)
                <form action="{{ route('pos-kantin.sales.destroy', $sale) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan transaksi ini?')">Batalkan</button>
                </form>
            @endcan
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Transaksi #{{ $sale->id }}</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt>Tanggal</dt><dd>{{ $sale->date->format('d/m/Y') }}</dd>
                    <dt>Pemasok</dt><dd>{{ $sale->supplier?->name ?? '-' }}</dd>
                    <dt>Petugas utama</dt><dd>{{ $sale->user?->name ?? '-' }}</dd>
                    <dt>Petugas tambahan</dt><dd>{{ collect($sale->additional_users ?? [])->implode(', ') ?: '-' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl>
                    <dt>Total pemasok</dt><dd>Rp {{ number_format($sale->total_supplier, 0, ',', '.') }}</dd>
                    <dt>Total kantin</dt><dd>Rp {{ number_format($sale->total_canteen, 0, ',', '.') }}</dd>
                    <dt>Status Pembayaran Pemasok</dt><dd><x-pos.status-badge :status="$sale->status_i" context="supplier-payment" /></dd>
                    <dt>Status Setoran Kantin</dt><dd><x-pos.status-badge :status="$sale->status_ii" context="canteen-deposit" /></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="card-body border-top">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-success mb-0">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Pembayaran Pemasok</h3>
                    </div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt>Status</dt><dd><x-pos.status-badge :status="$sale->status_i" context="supplier-payment" /></dd>
                            <dt>Tanggal pembayaran</dt><dd>{{ $sale->supplier_paid_at?->format('d/m/Y') ?? '-' }}</dd>
                            <dt>Nominal pembayaran</dt><dd>{{ $sale->supplier_paid_amount !== null ? 'Rp '.number_format($sale->supplier_paid_amount, 0, ',', '.') : '-' }}</dd>
                            <dt>Catatan pembayaran</dt><dd>{{ $sale->supplier_payment_note ?: '-' }}</dd>
                            <dt>Dikonfirmasi oleh</dt><dd>{{ $sale->supplierPaymentConfirmedBy?->name ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-outline card-warning mb-0">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Setoran Kantin</h3>
                    </div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt>Status</dt><dd><x-pos.status-badge :status="$sale->status_ii" context="canteen-deposit" /></dd>
                            <dt>Tanggal setoran</dt><dd>{{ $sale->canteen_deposited_at?->format('d/m/Y') ?? '-' }}</dd>
                            <dt>Nominal setoran</dt><dd>{{ $sale->canteen_deposited_amount !== null ? 'Rp '.number_format($sale->canteen_deposited_amount, 0, ',', '.') : '-' }}</dd>
                            <dt>Catatan setoran</dt><dd>{{ $sale->canteen_deposit_note ?: '-' }}</dd>
                            <dt>Dikonfirmasi oleh</dt><dd>{{ $sale->canteenDepositConfirmedBy?->name ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <x-pos.data-table :empty="$sale->items->isEmpty()" :colspan="7" empty-title="Belum ada item transaksi" empty-message="Rincian makanan akan muncul setelah item transaksi disimpan.">
            <x-slot:head>
                <tr>
                    <th>Makanan</th>
                    <th>Satuan</th>
                    <th>Jumlah</th>
                    <th>Sisa</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Potongan</th>
                </tr>
            </x-slot:head>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->food?->name ?? '-' }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ number_format($item->quantity) }}</td>
                    <td>{{ $item->leftover !== null ? number_format($item->leftover) : '-' }}</td>
                    <td>Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->total_item, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->cut_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
</div>
@endsection
