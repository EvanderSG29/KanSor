@extends('layouts.app')

@section('title', 'Edit Makanan POS')

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Ubah makanan</h3></div>
    <form method="POST" action="{{ route('pos-kantin.admin.foods.update', $food) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('pos-kantin.admin.foods._form')</div>
        <div class="card-footer">
            <button class="btn btn-primary">Perbarui</button>
            <a href="{{ route('pos-kantin.admin.foods.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection
