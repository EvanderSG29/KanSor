@extends('layouts.app')

@section('title', 'Koreksi Transaksi')

@section('content')
@include('kansor.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Form koreksi transaksi</h3></div>
    <form method="POST" action="{{ route('kansor.admin.sales.update', $sale) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('kansor.sales._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Simpan koreksi</button>
            <a href="{{ route('kansor.admin.sales.show', $sale) }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection

