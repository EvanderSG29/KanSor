@extends('layouts.app')

@section('title', 'Edit Transaksi Harian')
@section('page_subtitle', 'Perbarui item transaksi dan cek ulang ringkasan total sebelum menyimpan perubahan.')

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Ubah transaksi</h3></div>
    <form method="POST" action="{{ route('pos-kantin.sales.update', $sale) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('pos-kantin.sales._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Perbarui transaksi</button>
            <a href="{{ route('pos-kantin.sales.show', $sale) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
