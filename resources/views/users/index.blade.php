@extends('layouts.app', ['title' => 'Kelola User'])

@section('content')
    <h1>Kelola User</h1>

    <div class="card mb-12">
        <h2>Tambah User</h2>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="form-row">
                <input type="text" name="nama" placeholder="Nama" required>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="admin">Admin</option>
                    <option value="petugas">Petugas</option>
                    <option value="pemasok">Pemasok</option>
                </select>
                <button type="submit">Tambah</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Daftar User</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user['id'] }}</td>
                        <td>
                            <form method="POST" action="{{ route('users.update', $user['id']) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <input type="text" name="nama" value="{{ $user['nama'] }}" required>
                                    <input type="text" name="username" value="{{ $user['username'] }}" required>
                                    <input type="password" name="password" placeholder="Kosongkan jika tidak diganti">
                                    <select name="role" required>
                                        <option value="admin" @selected($user['role'] === 'admin')>Admin</option>
                                        <option value="petugas" @selected($user['role'] === 'petugas')>Petugas</option>
                                        <option value="pemasok" @selected($user['role'] === 'pemasok')>Pemasok</option>
                                    </select>
                                    <button type="submit">Update</button>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('users.destroy', $user['id']) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="danger" type="submit" onclick="return confirm('Hapus user ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Belum ada data user.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
