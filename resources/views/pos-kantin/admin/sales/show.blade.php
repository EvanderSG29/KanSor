@extends('layouts.app')

@section('title', 'Detail Konfirmasi Transaksi')

@section('content')
@include('pos-kantin.partials.alerts')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Transaksi #{{ $sale->id }}</h3>
                <a href="{{ route('pos-kantin.admin.sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary">Koreksi transaksi</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt>Tanggal</dt><dd>{{ $sale->date->format('d/m/Y') }}</dd>
                            <dt>Pemasok</dt><dd>{{ $sale->supplier?->name ?? '-' }}</dd>
                            <dt>Petugas</dt><dd>{{ $sale->user?->name ?? '-' }}</dd>
                            <dt>Catatan</dt><dd>{{ $sale->taken_note ?: '-' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl>
                            <dt>Total pemasok</dt><dd>Rp {{ number_format($sale->total_supplier, 0, ',', '.') }}</dd>
                            <dt>Total kantin</dt><dd>Rp {{ number_format($sale->total_canteen, 0, ',', '.') }}</dd>
                            <dt>Status I</dt><dd>{{ strtoupper($sale->status_i) }}</dd>
                            <dt>Status II</dt><dd>{{ strtoupper($sale->status_ii) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
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
                    </thead>
                    <tbody>
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
                    </tbody>
                </table>
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
                    <div class="form-group">
                        <label>Tanggal bayar</label>
                        <input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', optional($sale->paid_at)->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label>Nominal bayar</label>
                        <input type="text" name="paid_amount" class="form-control" value="{{ old('paid_amount', $sale->paid_amount) }}">
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <input type="text" name="taken_note" class="form-control" value="{{ old('taken_note', $sale->taken_note) }}">
                    </div>
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
                    <div class="form-group">
                        <label>Tanggal setoran</label>
                        <input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', optional($sale->paid_at)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Nominal setoran</label>
                        <input type="text" name="paid_amount" class="form-control" value="{{ old('paid_amount', $sale->paid_amount ?? $sale->total_canteen) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <input type="text" name="taken_note" class="form-control" value="{{ old('taken_note', $sale->taken_note) }}">
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-warning btn-block">Tandai disetor</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
