@extends('layouts.app')

@section('title', 'Detail Konfirmasi Admin')
@section('page_header')
    <x-pos.page-header title="Detail Konfirmasi Admin" subtitle="Konfirmasi pembayaran pemasok dan setoran kantin dari detail transaksi yang sama.">
        <x-slot:actions>
            <a href="{{ route('pos-kantin.admin.sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary">Koreksi transaksi</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('pos-kantin.partials.alerts')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Transaksi #{{ $sale->id }}</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt>Tanggal</dt><dd>{{ $sale->date->format('d/m/Y') }}</dd>
                            <dt>Pemasok</dt><dd>{{ $sale->supplier?->name ?? '-' }}</dd>
                            <dt>Petugas</dt><dd>{{ $sale->user?->name ?? '-' }}</dd>
                            <dt>Catatan transaksi</dt><dd>{{ $sale->taken_note ?: '-' }}</dd>
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
            <div class="card-body table-responsive p-0">
                <x-pos.data-table :empty="$sale->items->isEmpty()" :colspan="8" empty-title="Belum ada item transaksi" empty-message="Rincian item untuk proses konfirmasi admin akan muncul di sini.">
                    <x-slot:head>
                        <tr>
                            <th>Makanan</th>
                            <th>Satuan</th>
                            <th>Jumlah</th>
                            <th>Sisa</th>
                            <th>Harga</th>
                            <th>Total item</th>
                            <th>Potongan</th>
                            <th>Total pemasok</th>
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
                            <td>Rp {{ number_format($item->total_item - $item->cut_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </x-pos.data-table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Konfirmasi pemasok</h3></div>
            <form method="POST" action="{{ route('pos-kantin.admin.sales.confirm-supplier-paid', $sale) }}">
                @csrf
                @method('PATCH')
                <div class="card-body">
                    <dl class="mb-4">
                        <dt>Status saat ini</dt>
                        <dd><x-pos.status-badge :status="$sale->status_i" context="supplier-payment" /></dd>
                        <dt>Tanggal pembayaran</dt>
                        <dd>{{ $sale->supplier_paid_at?->format('d/m/Y') ?? '-' }}</dd>
                        <dt>Nominal pembayaran</dt>
                        <dd>{{ $sale->supplier_paid_amount !== null ? 'Rp '.number_format($sale->supplier_paid_amount, 0, ',', '.') : '-' }}</dd>
                        <dt>Catatan pembayaran</dt>
                        <dd>{{ $sale->supplier_payment_note ?: '-' }}</dd>
                        <dt>Dikonfirmasi oleh</dt>
                        <dd>{{ $sale->supplierPaymentConfirmedBy?->name ?? '-' }}</dd>
                    </dl>
                    <x-form.input id="supplier-paid-at" name="paid_at" label="Tanggal bayar" type="date" :value="optional($sale->supplier_paid_at)->format('Y-m-d')" />
                    <x-form.money id="supplier-paid-amount" name="paid_amount" label="Nominal bayar" :value="$sale->supplier_paid_amount" />
                    <x-form.input id="supplier-payment-note" name="taken_note" label="Catatan" :value="$sale->supplier_payment_note" />
                </div>
                <div class="card-footer"><button class="btn btn-success btn-block">Tandai dibayar</button></div>
            </form>
        </div>
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title">Konfirmasi setoran kantin</h3></div>
            <form method="POST" action="{{ route('pos-kantin.admin.sales.confirm-canteen-deposited', $sale) }}">
                @csrf
                @method('PATCH')
                <div class="card-body">
                    <dl class="mb-4">
                        <dt>Status saat ini</dt>
                        <dd><x-pos.status-badge :status="$sale->status_ii" context="canteen-deposit" /></dd>
                        <dt>Tanggal setoran</dt>
                        <dd>{{ $sale->canteen_deposited_at?->format('d/m/Y') ?? '-' }}</dd>
                        <dt>Nominal setoran</dt>
                        <dd>{{ $sale->canteen_deposited_amount !== null ? 'Rp '.number_format($sale->canteen_deposited_amount, 0, ',', '.') : '-' }}</dd>
                        <dt>Catatan setoran</dt>
                        <dd>{{ $sale->canteen_deposit_note ?: '-' }}</dd>
                        <dt>Dikonfirmasi oleh</dt>
                        <dd>{{ $sale->canteenDepositConfirmedBy?->name ?? '-' }}</dd>
                    </dl>
                    <x-form.input id="canteen-paid-at" name="paid_at" label="Tanggal setoran" type="date" :value="optional($sale->canteen_deposited_at)->format('Y-m-d')" required />
                    <x-form.money id="canteen-paid-amount" name="paid_amount" label="Nominal setoran" :value="$sale->canteen_deposited_amount ?? $sale->total_canteen" required />
                    <x-form.input id="canteen-deposit-note" name="taken_note" label="Catatan" :value="$sale->canteen_deposit_note" />
                </div>
                <div class="card-footer"><button class="btn btn-warning btn-block">Tandai disetor</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
