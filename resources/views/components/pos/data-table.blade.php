@props([
    'empty' => false,
    'colspan' => 1,
    'emptyTitle' => 'Belum ada data',
    'emptyMessage' => 'Data akan muncul setelah ada aktivitas yang relevan.',
    'emptyIcon' => 'fas fa-inbox',
    'responsive' => true,
    'hover' => true,
    'textNoWrap' => false,
])

<div @class(['table-responsive' => $responsive])>
    <table {{ $attributes->class([
        'table',
        'table-hover' => $hover,
        'text-nowrap' => $textNoWrap,
        'mb-0',
    ]) }}>
        @isset($head)
            <thead>
                {{ $head }}
            </thead>
        @endisset
        <tbody>
            @if ($empty)
                <tr>
                    <td colspan="{{ $colspan }}" class="p-0">
                        <x-pos.empty-state
                            :title="$emptyTitle"
                            :message="$emptyMessage"
                            :icon="$emptyIcon"
                            compact
                        />
                    </td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
        @isset($foot)
            <tfoot>
                {{ $foot }}
            </tfoot>
        @endisset
    </table>
</div>
