@props([
    'title',
    'subtitle' => null,
    'homeLabel' => 'Dashboard',
    'homeUrl' => auth()->check() ? route('home') : url('/'),
])

<div class="row mb-2">
    <div class="col-sm-8">
        <h1>{{ $title }}</h1>
        @if (filled($subtitle))
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="col-sm-4">
        @isset($actions)
            <div class="float-sm-right mb-2">
                {{ $actions }}
            </div>
        @endisset

        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item">
                <a href="{{ $homeUrl }}">{{ $homeLabel }}</a>
            </li>

            @isset($breadcrumbs)
                {{ $breadcrumbs }}
            @else
                <li class="breadcrumb-item active">{{ $title }}</li>
            @endisset
        </ol>
    </div>
</div>
