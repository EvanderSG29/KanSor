@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <h1>Dashboard</h1>
    <div class="grid cols-4">
        <div class="card">
            <div class="muted">Total Produk</div>
            <h2>{{ $productCount }}</h2>
        </div>
        <div class="card">
            <div class="muted">Total Stok</div>
            <h2>{{ $stockTotal }}</h2>
        </div>
        <div class="card">
            <div class="muted">Transaksi Hari Ini</div>
            <h2>{{ $todayTransactionCount }}</h2>
        </div>
        <div class="card">
            <div class="muted">Omzet Hari Ini</div>
            <h2>Rp {{ number_format($todaySales, 0, ',', '.') }}</h2>
        </div>
    </div>

    <div class="card mt-12">
        <h2>Ringkasan Hari Ini</h2>
        <p class="muted">Nilai pembelian barang masuk: Rp {{ number_format($todayIncomingValue, 0, ',', '.') }}</p>
    </div>
@endsection
