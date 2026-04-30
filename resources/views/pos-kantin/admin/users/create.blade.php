@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('page_header')
    <x-pos.page-header title="Tambah Pengguna" subtitle="Buat akun admin atau petugas baru dengan format form yang konsisten.">
        <x-slot:actions>
            <a href="{{ route('pos-kantin.admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('pos-kantin.partials.alerts')

<div class="card">
    <div class="card-header"><h3 class="card-title">Form pengguna baru</h3></div>
    <form method="POST" action="{{ route('pos-kantin.admin.users.store') }}">
        @csrf
        <div class="card-body">
            @include('pos-kantin.admin.users._form')
        </div>
        <div class="card-footer">
            <button class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
