@extends('layouts.app')

@section('title', 'Setup Database Lokal')
@section('body_class', 'hold-transition login-page')
@section('auth_page', true)

@section('content')
<div class="login-box" style="width:min(100%,34rem);">
    @include('pos-kantin.partials.alerts')

    <div class="card card-outline card-warning">
        <div class="card-header text-center">
            <h1 class="h4 mb-1">Setup database lokal diperlukan</h1>
            <p class="text-muted mb-0">Aplikasi menahan akses sementara agar tidak masuk ke halaman yang masih menghasilkan error atau 403 palsu.</p>
        </div>
        <div class="card-body">
            <div class="callout callout-warning">
                <h5 class="mb-2">Migrasi pending terdeteksi</h5>
                <p class="mb-0">Jalankan setup sekali untuk menyinkronkan schema SQLite lokal dengan kode terbaru.</p>
            </div>

            <div class="mb-3">
                <div class="small text-muted mb-2">Halaman yang sedang diblokir</div>
                <div class="border rounded px-3 py-2 bg-light text-monospace text-break">{{ $blockedUrl }}</div>
            </div>

            <div class="mb-4">
                <div class="small text-muted mb-2">Daftar migrasi yang masih pending</div>
                <ul class="list-group">
                    @foreach ($schemaReadiness['pendingMigrations'] as $migration)
                        <li class="list-group-item d-flex align-items-center justify-content-between">
                            <span class="text-monospace">{{ $migration }}</span>
                            <span class="badge badge-warning">Pending</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row" style="gap:0.75rem;">
                <button
                    type="button"
                    class="btn btn-warning btn-block"
                    data-setup-run
                    data-run-url="{{ route('setup.run-migrations') }}"
                >
                    <i class="fas fa-database mr-1"></i>
                    Jalankan migrasi lokal
                </button>
                <button type="button" class="btn btn-outline-secondary btn-block" data-app-shell-refresh>
                    <i class="fas fa-sync-alt mr-1"></i>
                    Cek lagi
                </button>
            </div>

            <div class="alert alert-danger mt-3 mb-0 d-none" data-setup-feedback></div>
            <pre class="bg-dark text-light rounded p-3 mt-3 mb-0 d-none" style="max-height:16rem; overflow:auto;" data-setup-output></pre>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const runButton = document.querySelector('[data-setup-run]');
            const feedback = document.querySelector('[data-setup-feedback]');
            const output = document.querySelector('[data-setup-output]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            if (! runButton) {
                return;
            }

            const resetFeedback = () => {
                feedback?.classList.add('d-none');
                output?.classList.add('d-none');

                if (feedback) {
                    feedback.textContent = '';
                }

                if (output) {
                    output.textContent = '';
                }
            };

            const showFailure = (message, commandOutput = '') => {
                if (feedback) {
                    feedback.textContent = message;
                    feedback.classList.remove('d-none');
                }

                if (output && commandOutput.trim() !== '') {
                    output.textContent = commandOutput;
                    output.classList.remove('d-none');
                }
            };

            runButton.addEventListener('click', async () => {
                runButton.disabled = true;
                resetFeedback();
                window.KanSorAppShellUi?.showLoading('Menjalankan migrasi lokal...');

                try {
                    const response = await fetch(runButton.dataset.runUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const payload = await response.json();
                    const result = payload.data ?? {};

                    if (response.ok && result.success === true) {
                        window.location.reload();
                        return;
                    }

                    showFailure(
                        result.message ?? 'Migrasi lokal gagal dijalankan.',
                        result.output ?? '',
                    );
                } catch (error) {
                    showFailure('Migrasi lokal gagal dijalankan. Periksa terminal aplikasi untuk detail tambahan.');
                } finally {
                    window.KanSorAppShellUi?.hideLoading();
                    runButton.disabled = false;
                }
            });
        });
    </script>
@endpush
