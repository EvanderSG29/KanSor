@extends('layouts.app', ['title' => 'Transaksi'])

@section('content')
    <h1>Transaksi Penjualan</h1>

    <div class="card mb-12">
        <h2>Input Transaksi</h2>
        <form method="POST" action="{{ route('transaksi.store') }}" id="transaction-form">
            @csrf
            <div id="items-wrap">
                <div class="form-row item-row">
                    <select name="produk_id[]" required>
                        <option value="">Pilih produk</option>
                        @foreach($products as $product)
                            @if((int) $product['stok'] > 0)
                                <option value="{{ $product['id'] }}">{{ $product['nama_produk'] }} (stok: {{ $product['stok'] }})</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="number" name="qty[]" min="1" placeholder="Qty" required>
                </div>
            </div>
            <div class="form-row" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                <select name="status" required>
                    <option value="lunas">Lunas</option>
                    <option value="belum_lunas">Belum Lunas</option>
                </select>
                <button type="button" class="secondary" onclick="addItemRow()">Tambah Item</button>
                <button type="submit">Simpan Transaksi</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Riwayat Transaksi (30 Terakhir)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction['id'] }}</td>
                        <td>{{ $transaction['tanggal'] }}</td>
                        <td>Rp {{ number_format((int) $transaction['total'], 0, ',', '.') }}</td>
                        <td>{{ $transaction['status'] }}</td>
                        <td>
                            @foreach($detailsByTransaction[$transaction['id']] ?? [] as $detail)
                                @php($product = $productById[$detail['produk_id']] ?? null)
                                <div>
                                    {{ $product['nama_produk'] ?? ('Produk #'.$detail['produk_id']) }}
                                    - {{ $detail['qty'] }} pcs
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function addItemRow() {
            const wrap = document.getElementById('items-wrap');
            const firstRow = wrap.querySelector('.item-row');
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach((el) => el.value = '');
            clone.querySelectorAll('select').forEach((el) => el.selectedIndex = 0);
            wrap.appendChild(clone);
        }
    </script>
@endsection
