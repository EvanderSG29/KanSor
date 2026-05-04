@extends('layouts.app')

@section('title', 'Tambah Menu / Makanan')
@section('page_header')
    <x-pos.page-header title="Tambah Menu / Makanan" subtitle="Siapkan item jual baru agar siap dipakai pada transaksi harian.">
        <x-slot:actions>
            <a href="{{ route('kansor.admin.foods.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('kansor.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Form makanan baru</h3></div>
    <form method="POST" action="{{ route('kansor.admin.foods.store') }}">
        @csrf
        <div class="card-body">@include('kansor.admin.foods._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection

