@extends('layouts.app', ['title' => 'Barang Masuk'])

@section('content')
    <h1>Barang Masuk</h1>

    <div class="card mb-12">
        <h2>Input Barang Masuk</h2>
        <form method="POST" action="{{ route('barang-masuk.store') }}">
            @csrf
            <div class="form-row">
                <select name="produk_id" required>
                    <option value="">Pilih produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product['id'] }}">{{ $product['nama_produk'] }}</option>
                    @endforeach
                </select>
                <input type="number" name="qty" min="1" placeholder="Qty" required>
                <input type="number" name="harga" min="0" placeholder="Harga beli terbaru" required>
                <button type="submit">Simpan</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Riwayat Barang Masuk (30 Terakhir)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Qty</th>
                    <th>Harga</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incomingGoods as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['tanggal'] }}</td>
                        <td>{{ $productById[$row['produk_id']]['nama_produk'] ?? ('Produk #'.$row['produk_id']) }}</td>
                        <td>{{ $row['qty'] }}</td>
                        <td>Rp {{ number_format((int) $row['harga'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada data barang masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
