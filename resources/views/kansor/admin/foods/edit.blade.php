@extends('layouts.app')

@section('title', 'Edit Menu / Makanan')
@section('page_header')
    <x-pos.page-header title="Edit Menu / Makanan" subtitle="Perbarui katalog makanan, pemasok, dan harga default secara konsisten.">
        <x-slot:actions>
            <a href="{{ route('kansor.admin.foods.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('kansor.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Ubah makanan</h3></div>
    <form method="POST" action="{{ route('kansor.admin.foods.update', $food) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('kansor.admin.foods._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Perbarui</button>
        </div>
    </form>
</div>
@endsection

