@extends('layouts.app')

@section('title', 'Audit Aktivitas')
@section('page_header')
    <x-pos.page-header title="Audit Aktivitas" subtitle="Lacak aksi admin, sinkronisasi, dan perubahan sensitif dari satu tempat.">
        <x-slot:actions>
            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">Kembali ke dashboard</a>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@section('content')
@include('pos-kantin.partials.alerts')

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Audit aktivitas admin dan sinkronisasi</h3>
    </div>
    <x-pos.filter-card :action="route('pos-kantin.admin.audit-logs.index')" :reset-url="route('pos-kantin.admin.audit-logs.index')" title="Filter audit log" card-class="border-0 shadow-none mb-0">
            <div class="col-md-4">
                <x-form.select id="action" name="action" label="Aksi">
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </x-form.select>
            </div>
    </x-pos.filter-card>
    <div class="card-body table-responsive p-0">
        <x-pos.data-table :empty="$auditLogs->isEmpty()" :colspan="5" empty-title="Belum ada audit log" empty-message="Aksi sensitif akan otomatis tercatat di sini saat mulai digunakan.">
            <x-slot:head>
                <tr>
                    <th>Waktu</th>
                    <th>Pelaku</th>
                    <th>Aksi</th>
                    <th>Subjek</th>
                    <th>Metadata</th>
                </tr>
            </x-slot:head>
            @foreach ($auditLogs as $auditLog)
                <tr>
                    <td>{{ $auditLog->created_at?->format('d M Y H:i:s') ?? '-' }}</td>
                    <td>{{ $auditLog->actor?->name ?? '-' }}</td>
                    <td><code>{{ $auditLog->action }}</code></td>
                    <td>
                        <div>{{ class_basename((string) $auditLog->subject_type) ?: '-' }}</div>
                        <small class="text-muted">{{ $auditLog->subject_id ?: '-' }}</small>
                    </td>
                    <td>
                        @if ($auditLog->metadata)
                            <pre class="mb-0 text-wrap">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
    <div class="card-footer">{{ $auditLogs->links() }}</div>
</div>
@endsection
