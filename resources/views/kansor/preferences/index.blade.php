@extends('layouts.app')

@section('title', 'Preferensi')

@section('content')
@include('kansor.partials.alerts')
<div class="card">
    <div class="card-header"><h3 class="card-title">Preferensi pribadi</h3></div>
    <form method="POST" action="{{ route('kansor.preferences.store') }}">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Interval sinkronisasi (detik)</label>
                        <input type="number" min="10" max="3600" name="sync_interval" class="form-control" value="{{ old('sync_interval', $preferences['sync_interval']) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tema</label>
                        <select name="theme" class="form-control">
                            @foreach (['system' => 'Ikuti sistem', 'light' => 'Terang', 'dark' => 'Gelap'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('theme', $preferences['theme']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Baris per halaman</label>
                        <input type="number" min="5" max="100" name="rows_per_page" class="form-control" value="{{ old('rows_per_page', $preferences['rows_per_page']) }}" required>
                    </div>
                                <div class="col-md-4">
                    <div class="form-group">
                        <label>Durasi login offline (hari)</label>
<<<<<<< HEAD:resources/views/kansor/preferences/index.blade.php
                        <input type="number" min="1" max="{{ (int) config('services.kansor.offline_login_days_max', 30) }}" name="offline_session_days" class="form-control" value="{{ old('offline_session_days', $preferences['offline_session_days']) }}" required>
=======
                        <input type="number" min="1" max="{{ (int) config('services.pos_kantin.offline_login_days_max', 30) }}" name="offline_session_days" class="form-control" value="{{ old('offline_session_days', $preferences['offline_session_days']) }}" required>
>>>>>>> c97eb59 (Enhance AdminLTE UX with toast/modal and clean obsolete dev notes):resources/views/pos-kantin/preferences/index.blade.php
                    </div>
                </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary">Simpan preferensi</button>
        </div>
    </form>
</div>
@endsection

<<<<<<< HEAD:resources/views/kansor/preferences/index.blade.php
=======

>>>>>>> 6549984 (	modified:   .env.example):resources/views/pos-kantin/preferences/index.blade.php
