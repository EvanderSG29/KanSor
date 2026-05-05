@php
    $flashMessages = collect([
        ['type' => 'success', 'message' => session('status')],
        ['type' => 'danger', 'message' => session('error')],
        ['type' => 'warning', 'message' => session('warning')],
    ])->filter(fn (array $item): bool => filled($item['message']))->values();

    $syncNoticeStatus = session('sync_notice_status');
    $syncNoticeMessage = session('sync_notice_message');
    $syncNoticeClass = match ($syncNoticeStatus) {
        'applied' => 'success',
        'queued' => 'info',
        'unsupported' => 'warning',
        'failed' => 'danger',
        default => null,
    };
@endphp

@foreach ($flashMessages as $flash)
    <div class="alert alert-{{ $flash['type'] }} alert-dismissible fade show" role="alert" data-kansor-toast-message="{{ $flash['message'] }}" data-kansor-toast-type="{{ $flash['type'] }}">
        {{ $flash['message'] }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endforeach

@if ($syncNoticeClass && $syncNoticeMessage)
    <div class="alert alert-{{ $syncNoticeClass }} alert-dismissible fade show" role="alert" data-kansor-toast-message="Sinkronisasi {{ strtoupper((string) $syncNoticeStatus) }}: {{ $syncNoticeMessage }}" data-kansor-toast-type="{{ $syncNoticeClass }}">
        <strong>Sinkronisasi {{ strtoupper($syncNoticeStatus) }}:</strong>
        {{ $syncNoticeMessage }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert" data-kansor-toast-message="Terdapat {{ $errors->count() }} error validasi. Periksa input Anda." data-kansor-toast-type="danger">
        <strong>Periksa kembali input berikut:</strong>
        <ul class="mb-0 mt-2 pl-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif


