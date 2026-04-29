@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
@isset($errorMessage)
@if ($errorMessage)
    <div class="alert alert-danger">
        {{ $errorMessage }}
    </div>
@endif
@endisset

<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($summary['count'] ?? 0) }}</h3>
                <p>Total pengguna</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($summary['activeCount'] ?? 0) }}</h3>
                <p>Pengguna aktif</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($summary['adminCount'] ?? 0) }}</h3>
                <p>Admin</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar pengguna backend</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Diperbarui</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user['fullName'] }}</strong>
                            <div class="text-muted small">{{ $user['nickname'] }}</div>
                        </td>
                        <td>{{ $user['email'] }}</td>
                        <td>{{ strtoupper($user['role']) }}</td>
                        <td>
                            <span class="badge badge-{{ $user['status'] === 'aktif' ? 'success' : 'secondary' }}">
                                {{ ucfirst($user['status']) }}
                            </span>
                        </td>
                        <td>{{ $user['updatedAt'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada data pengguna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
