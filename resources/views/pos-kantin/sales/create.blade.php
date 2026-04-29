@extends('layouts.app')

@section('title', 'Input Transaksi Harian')

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Form transaksi baru</h3></div>
    <form method="POST" action="{{ route('pos-kantin.sales.store') }}">
        @csrf
        <div class="card-body">@include('pos-kantin.sales._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Simpan transaksi</button>
            <a href="{{ route('pos-kantin.sales.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection
