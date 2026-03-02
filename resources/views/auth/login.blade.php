@extends('layouts.app', ['title' => 'Login'])

@section('content')
    <div class="card" style="max-width:420px;margin:40px auto;">
        <h1>Login Kantin Sore</h1>
        <p class="muted mb-12">Gunakan akun default: <strong>admin / admin123</strong> (ganti setelah login).</p>
        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="form-row" style="grid-template-columns:1fr;">
                <input type="text" name="username" placeholder="Username" value="{{ old('username') }}" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Masuk</button>
            </div>
        </form>
    </div>
@endsection
