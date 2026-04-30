@extends('layouts.app')

@section('title', 'Tambah Pemasok')
@section('page_header')
    <x-pos.page-header title="Tambah Pemasok" subtitle="Daftarkan pemasok baru beserta kontak dan persentase potongannya.">
        <x-slot:actions>
            <a href="{{ route('pos-kantin.admin.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Form pemasok baru</h3></div>
    <form method="POST" action="{{ route('pos-kantin.admin.suppliers.store') }}">
        @csrf
        <div class="card-body">@include('pos-kantin.admin.suppliers._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
