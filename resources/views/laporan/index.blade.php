@extends('layouts.app', ['title' => 'Laporan'])

@section('content')
    <h1>Laporan Keuangan</h1>

    <div class="card mb-12">
        <h2>Filter</h2>
        <form method="GET" action="{{ route('laporan.index') }}">
            <div class="form-row" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                <input type="date" name="start" value="{{ $start }}" required>
                <input type="date" name="end" value="{{ $end }}" required>
                <button type="submit">Terapkan</button>
                <a href="{{ route('laporan.export', ['start' => $start, 'end' => $end]) }}" style="text-decoration:none;">
                    <span style="display:inline-block;padding:8px;border-radius:6px;background:#4b5563;color:#fff;font-size:14px;">Export CSV</span>
                </a>
            </div>
        </form>
    </div>

    <div class="grid cols-4 mb-12">
        <div class="card">
            <div class="muted">Total Penjualan</div>
            <h2>Rp {{ number_format($salesTotal, 0, ',', '.') }}</h2>
        </div>
        <div class="card">
            <div class="muted">Total Pembelian</div>
            <h2>Rp {{ number_format($purchaseTotal, 0, ',', '.') }}</h2>
        </div>
        <div class="card">
            <div class="muted">Margin Kotor</div>
            <h2>Rp {{ number_format($grossMargin, 0, ',', '.') }}</h2>
        </div>
        <div class="card">
            <div class="muted">Jumlah Transaksi</div>
            <h2>{{ $transactionCount }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Transaksi (50 Terakhir dalam Rentang)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Petugas</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction['id'] }}</td>
                        <td>{{ $transaction['tanggal'] }}</td>
                        <td>{{ $transaction['petugas_id'] }}</td>
                        <td>Rp {{ number_format((int) $transaction['total'], 0, ',', '.') }}</td>
                        <td>{{ $transaction['status'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Tidak ada data transaksi pada rentang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <p class="muted mt-12">Jumlah data barang masuk dalam rentang: {{ $incomingCount }}</p>
    </div>
@endsection
