@extends('layouts.app')

@section('title', 'Input Transaksi Harian')
@section('page_subtitle', 'Catat transaksi harian dengan ringkasan otomatis sebelum disimpan.')

@section('content')
@include('kansor.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Form transaksi baru</h3></div>
    <form method="POST" action="{{ route('kansor.sales.store') }}">
        @csrf
        <div class="card-body">@include('kansor.sales._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Simpan transaksi</button>
            <a href="{{ route('kansor.sales.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection

