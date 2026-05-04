@extends('layouts.app')

@section('title', 'Edit Transaksi Harian')
@section('page_subtitle', 'Perbarui item transaksi dan cek ulang ringkasan total sebelum menyimpan perubahan.')

@section('content')
@include('kansor.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Ubah transaksi</h3></div>
    <form method="POST" action="{{ route('kansor.sales.update', $sale) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('kansor.sales._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Perbarui transaksi</button>
            <a href="{{ route('kansor.sales.show', $sale) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

