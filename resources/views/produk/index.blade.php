@extends('layouts.app', ['title' => 'Produk'])

@section('content')
    @php($authUser = session('auth_user'))
    <h1>Produk</h1>

    @if(in_array($authUser['role'] ?? '', ['admin', 'petugas'], true))
        <div class="card mb-12">
            <h2>Tambah Produk</h2>
            <form method="POST" action="{{ route('produk.store') }}">
                @csrf
                <div class="form-row">
                    <input type="text" name="nama_produk" placeholder="Nama produk" required>
                    <input type="number" name="harga_jual" placeholder="Harga jual" min="0" required>
                    <input type="number" name="stok" placeholder="Stok" min="0" required>
                    <input type="number" name="harga_beli" placeholder="Harga beli" min="0" required>
                    <button type="submit">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card">
        <h2>Daftar Produk</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Harga Jual</th>
                    <th>Stok</th>
                    <th>Harga Beli</th>
                    @if(in_array($authUser['role'] ?? '', ['admin', 'petugas'], true))
                        <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product['id'] }}</td>
                        @if(in_array($authUser['role'] ?? '', ['admin', 'petugas'], true))
                            <td colspan="5">
                                <form method="POST" action="{{ route('produk.update', $product['id']) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <input type="text" name="nama_produk" value="{{ $product['nama_produk'] }}" required>
                                        <input type="number" name="harga_jual" value="{{ $product['harga_jual'] }}" min="0" required>
                                        <input type="number" name="stok" value="{{ $product['stok'] }}" min="0" required>
                                        <input type="number" name="harga_beli" value="{{ $product['harga_beli'] }}" min="0" required>
                                        <button type="submit">Update</button>
                                    </div>
                                </form>
                                @if(($authUser['role'] ?? '') === 'admin')
                                    <form method="POST" action="{{ route('produk.destroy', $product['id']) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger" type="submit" onclick="return confirm('Hapus produk ini?')">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        @else
                            <td>{{ $product['nama_produk'] }}</td>
                            <td>Rp {{ number_format((int) $product['harga_jual'], 0, ',', '.') }}</td>
                            <td>{{ $product['stok'] }}</td>
                            <td>Rp {{ number_format((int) $product['harga_beli'], 0, ',', '.') }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada data produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
