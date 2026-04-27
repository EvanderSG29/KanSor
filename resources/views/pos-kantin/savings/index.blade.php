@extends('layouts.app')

@section('title', 'Simpanan')

@section('content')
@if ($errorMessage)
    <div class="alert alert-danger">
        {{ $errorMessage }}
    </div>
@endif

<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($summary['count'] ?? 0) }}</h3>
                <p>Total catatan</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp {{ number_format($summary['depositAmount'] ?? 0, 0, ',', '.') }}</h3>
                <p>Total setoran</p>
            </div>
            <div class="icon">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>Rp {{ number_format($summary['changeBalance'] ?? 0, 0, ',', '.') }}</h3>
                <p>Saldo kembalian</p>
            </div>
            <div class="icon">
                <i class="fas fa-coins"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar simpanan</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Setoran</th>
                    <th>Kembalian</th>
                    <th>Pencatat</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($savings as $saving)
                    <tr>
                        <td>{{ $saving['studentName'] }}</td>
                        <td>{{ $saving['className'] }}</td>
                        <td>Rp {{ number_format($saving['depositAmount'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($saving['changeBalance'], 0, ',', '.') }}</td>
                        <td>{{ $saving['recordedByName'] }}</td>
                        <td>{{ $saving['recordedAt'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada data simpanan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
