<header class="kansor-native-titlebar" aria-label="KanSor native titlebar">
    <div class="kansor-native-titlebar__left">
        <a
            href="{{ auth()->check() ? route('home') : url('/') }}"
            class="kansor-native-titlebar__brand"
            data-skip-loading
            title="{{ config('app.name', 'KanSor') }}"
        >
            <i class="fas fa-store"></i>
            <span>{{ config('app.name', 'KanSor') }}</span>
        </a>

        @auth
            <div class="kansor-native-titlebar__history" role="group" aria-label="Navigasi halaman">
                <button type="button" data-app-shell-back disabled title="Kembali">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="button" data-app-shell-forward disabled title="Maju">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        @endauth

        <nav class="kansor-native-titlebar__menu" aria-label="Menu aplikasi">
            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    File
                </button>

                <div class="dropdown-menu">
                    @auth
                        <a class="dropdown-item" href="{{ route('home') }}">
                            Dashboard
                        </a>
                    @else
                        <a class="dropdown-item" href="{{ route('login') }}">
                            Login
                        </a>
                    @endauth

                    <button type="button" class="dropdown-item" data-native-window-control="reload">
                        Reload
                    </button>

                    <div class="dropdown-divider"></div>

                    <button type="button" class="dropdown-item text-danger" data-native-window-control="close">
                        Keluar
                    </button>
                </div>
            </div>

            <button type="button" class="kansor-native-titlebar__menu-button" disabled>
                Edit
            </button>

            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    View
                </button>

                <div class="dropdown-menu">
                    <button type="button" class="dropdown-item" data-native-window-control="reload">
                        Refresh halaman
                    </button>

                    @auth
                        <button type="button" class="dropdown-item" data-widget="pushmenu">
                            Toggle sidebar
                        </button>
                    @endauth
                </div>
            </div>

            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    Window
                </button>

                <div class="dropdown-menu">
                    <button type="button" class="dropdown-item" data-native-window-control="minimize">
                        Minimize
                    </button>
                    <button type="button" class="dropdown-item" data-native-window-control="maximize">
                        Maximize
                    </button>
                    <button type="button" class="dropdown-item" data-native-window-control="reload">
                        Reload
                    </button>
                    <div class="dropdown-divider"></div>
                    <button type="button" class="dropdown-item text-danger" data-native-window-control="close">
                        Close
                    </button>
                </div>
            </div>

            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    Help
                </button>

                <div class="dropdown-menu">
                    <span class="dropdown-item-text">
                        {{ config('app.name', 'KanSor') }}
                    </span>
                </div>
            </div>
        </nav>
    </div>

    <div class="kansor-native-titlebar__right">
        @auth
            <span class="kansor-native-titlebar__user" title="{{ Auth::user()->name }}">
                <i class="far fa-user"></i>
                <span>{{ Auth::user()->name }}</span>
            </span>
        @endauth

        <div class="kansor-native-titlebar__window-controls" role="group" aria-label="Window controls">
            <button type="button" data-native-window-control="minimize" title="Minimize">
                <i class="fas fa-minus"></i>
            </button>

            <button type="button" data-native-window-control="maximize" title="Maximize">
                <i class="far fa-square"></i>
            </button>

            <button type="button" data-native-window-control="close" title="Close" class="is-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</header>
