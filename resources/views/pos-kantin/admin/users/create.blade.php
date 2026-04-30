@extends('layouts.app')

@section('title', 'Tambah Pengguna')

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
            <a href="{{ route('pos-kantin.admin.users.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection
