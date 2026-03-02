<?php

namespace App\Http\Controllers;

use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly CsvService $csv)
    {
    }

    public function index(): View
    {
        $users = $this->csv->read('users');
        usort($users, fn (array $a, array $b): int => ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)));

        return view('users.index', ['users' => $users]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'petugas', 'pemasok'])],
        ]);

        $existing = $this->csv->findOne('users', 'username', $data['username']);
        if ($existing !== null) {
            return back()->with('error', 'Username sudah dipakai.');
        }

        $this->csv->insert('users', [
            'nama' => $data['nama'],
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50'],
            'role' => ['required', Rule::in(['admin', 'petugas', 'pemasok'])],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $users = $this->csv->read('users');
        foreach ($users as $user) {
            if ($user['id'] === $id) {
                continue;
            }

            if (($user['username'] ?? '') === $data['username']) {
                return back()->with('error', 'Username sudah dipakai user lain.');
            }
        }

        $updates = [
            'nama' => $data['nama'],
            'username' => $data['username'],
            'role' => $data['role'],
        ];

        if (! empty($data['password'])) {
            $updates['password_hash'] = Hash::make($data['password']);
        }

        $updated = $this->csv->updateById('users', $id, $updates);
        if ($updated === null) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        return back()->with('success', 'User berhasil diubah.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $authUser = $request->session()->get('auth_user');
        if ((string) ($authUser['id'] ?? '') === $id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun yang sedang digunakan.');
        }

        $users = $this->csv->read('users');
        $target = null;
        foreach ($users as $user) {
            if (($user['id'] ?? null) === $id) {
                $target = $user;
                break;
            }
        }

        if ($target === null) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        if (($target['role'] ?? null) === 'admin') {
            $adminCount = count(array_filter($users, fn (array $row): bool => ($row['role'] ?? null) === 'admin'));
            if ($adminCount <= 1) {
                return back()->with('error', 'Minimal harus ada 1 akun admin.');
            }
        }

        $this->csv->deleteById('users', $id);

        return back()->with('success', 'User berhasil dihapus.');
    }
}
