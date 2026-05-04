@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('page_header')
    <x-pos.page-header title="Edit Pengguna" subtitle="Perbarui identitas, peran, dan status akses pengguna.">
        <x-slot:actions>
            <a href="{{ route('kansor.admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('kansor.partials.alerts')

<div class="card">
    <div class="card-header"><h3 class="card-title">Ubah pengguna</h3></div>
    <form method="POST" action="{{ route('kansor.admin.users.update', $userModel) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('kansor.admin.users._form')
        </div>
        <div class="card-footer">
            <button class="btn btn-primary">Perbarui</button>
        </div>
    </form>
</div>
@endsection

