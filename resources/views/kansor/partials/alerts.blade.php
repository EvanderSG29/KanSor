@if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning">
        {{ session('warning') }}
    </div>
@endif

@php
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

@if ($syncNoticeClass && $syncNoticeMessage)
    <div class="alert alert-{{ $syncNoticeClass }}">
        <strong>Sinkronisasi {{ strtoupper($syncNoticeStatus) }}:</strong>
        {{ $syncNoticeMessage }}
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div class="alert alert-danger">
        <strong>Periksa kembali input berikut:</strong>
        <ul class="mb-0 mt-2 pl-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
