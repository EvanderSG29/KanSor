@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Selamat datang di {{ config('app.name', 'KanSor') }}</h3>
            </div>
            <div class="card-body">
                <h5>Template AdminLTE sudah siap dipakai.</h5>
                <p class="mb-0">Gunakan halaman ini sebagai dasar untuk menyusun tampilan dashboard, kartu statistik, tabel, dan form dari contoh AdminLTE.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-store"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">KanSor</span>
                <span class="info-box-number">Dashboard Kantin</span>
            </div>
        </div>
    </div>
</div>
@endsection
