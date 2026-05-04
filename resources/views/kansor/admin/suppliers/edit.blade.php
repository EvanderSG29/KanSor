@extends('layouts.app')

@section('title', 'Edit Pemasok')
@section('page_header')
    <x-pos.page-header title="Edit Pemasok" subtitle="Ubah detail pemasok tanpa mengganggu histori transaksi yang sudah ada.">
        <x-slot:actions>
            <a href="{{ route('kansor.admin.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('kansor.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Ubah pemasok</h3></div>
    <form method="POST" action="{{ route('kansor.admin.suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('kansor.admin.suppliers._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Perbarui</button>
        </div>
    </form>
</div>
@endsection

