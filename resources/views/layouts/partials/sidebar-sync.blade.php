<li class="nav-header">SINKRONISASI</li>
<li class="nav-item">
    <a href="{{ route('kansor.sync.index') }}" class="nav-link {{ request()->routeIs('kansor.sync.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-sync-alt"></i>
        <p>
            Status Sinkronisasi
            @if ($syncAttentionCount > 0)
                <span class="right badge badge-danger">{{ $syncAttentionCount }}</span>
            @elseif ($syncQueuedCount > 0)
                <span class="right badge badge-warning">{{ $syncQueuedCount }}</span>
            @endif
        </p>
    </a>
</li>

@if (($showServerData ?? false) === true)
    @php
        $isServerDataMenuActive = request()->routeIs('kansor.transactions.*')
            || request()->routeIs('kansor.suppliers.*')
            || request()->routeIs('kansor.users.*')
            || request()->routeIs('kansor.savings.*');
    @endphp
    <li class="nav-item has-treeview {{ $isServerDataMenuActive ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $isServerDataMenuActive ? 'active' : '' }}">
            <i class="nav-icon fas fa-database"></i>
            <p>
                Data Server
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('kansor.transactions.index') }}" class="nav-link {{ request()->routeIs('kansor.transactions.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Transaksi Server</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('kansor.suppliers.index') }}" class="nav-link {{ request()->routeIs('kansor.suppliers.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Pemasok Server</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('kansor.users.index') }}" class="nav-link {{ request()->routeIs('kansor.users.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Pengguna Server</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('kansor.savings.index') }}" class="nav-link {{ request()->routeIs('kansor.savings.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Simpanan Server</p>
                </a>
            </li>
        </ul>
    </li>
@endif

