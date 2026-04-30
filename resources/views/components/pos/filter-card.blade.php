@props([
    'title' => null,
    'action' => null,
    'method' => 'GET',
    'resetUrl' => null,
    'cardClass' => 'card-outline card-primary',
])

@php
    $httpMethod = strtoupper($method);
@endphp

<div {{ $attributes->class(['card', $cardClass]) }}>
    @if (filled($title))
        <div class="card-header">
            <h3 class="card-title mb-0">{{ $title }}</h3>
        </div>
    @endif

    <form method="{{ $httpMethod === 'GET' ? 'GET' : 'POST' }}" action="{{ $action ?? url()->current() }}">
        @if ($httpMethod !== 'GET')
            @csrf
        @endif

        @if (! in_array($httpMethod, ['GET', 'POST'], true))
            @method($httpMethod)
        @endif

        <div class="card-body">
            <div class="row">
                {{ $slot }}
            </div>

            <div class="mt-3 d-flex flex-wrap align-items-center">
                @isset($actions)
                    {{ $actions }}
                @else
                    <button type="submit" class="btn btn-outline-primary mr-2 mb-2">Filter</button>

                    @if ($resetUrl)
                        <a href="{{ $resetUrl }}" class="btn btn-outline-secondary mb-2">Reset</a>
                    @endif
                @endisset
            </div>
        </div>
    </form>
</div>
