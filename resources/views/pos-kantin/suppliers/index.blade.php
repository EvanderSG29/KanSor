@extends('layouts.app')

@section('title', 'Pemasok')

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Filter pemasok</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('pos-kantin.suppliers.index') }}">
            <div class="form-check">
                <input
                    id="includeInactive"
                    type="checkbox"
                    name="includeInactive"
                    value="1"
                    class="form-check-input"
                    @checked($filters['includeInactive'] ?? false)
                >
                <label class="form-check-label" for="includeInactive">Tampilkan pemasok nonaktif</label>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Terapkan</button>
        </form>
    </div>
</div>

@if ($errorMessage)
    <div class="alert alert-danger">
        {{ $errorMessage }}
    </div>
@endif

<div class="row">
    <div class="col-lg-6 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($summary['count'] ?? 0) }}</h3>
                <p>Total pemasok</p>
            </div>
            <div class="icon">
                <i class="fas fa-truck"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($summary['activeCount'] ?? 0) }}</h3>
                <p>Pemasok aktif</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Master pemasok</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Komisi</th>
                    <th>Termin</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier['supplierName'] }}</td>
                        <td>
                            <div>{{ $supplier['contactName'] ?: '-' }}</div>
                            <div class="text-muted small">{{ $supplier['contactPhone'] ?: '-' }}</div>
                        </td>
                        <td>{{ number_format($supplier['commissionRate'], 0, ',', '.') }}%</td>
                        <td>{{ number_format($supplier['payoutTermDays']) }} hari</td>
                        <td>
                            <span class="badge badge-{{ $supplier['isActive'] ? 'success' : 'secondary' }}">
                                {{ $supplier['isActive'] ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada data pemasok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
