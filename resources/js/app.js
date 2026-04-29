import $ from 'jquery';

window.$ = $;
window.jQuery = $;

await import('admin-lte/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js');
await import('admin-lte/dist/js/adminlte.min.js');

const nativeDesktopConfig = window.KanSorNativeDesktop ?? null;
const syncConfig = window.KanSorSync ?? null;

if (syncConfig) {
    const syncIndicator = document.querySelector('[data-sync-indicator]');
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';
    let lastAutoRunAt = 0;
    let statusRequestInFlight = false;
    let syncRequestInFlight = false;

    const renderSyncIndicator = (status = {}) => {
        if (!syncIndicator) {
            return;
        }

        const pendingCount = Number(status.pendingCount ?? 0);
        const conflictCount = Number(status.conflictCount ?? 0);
        const failedCount = Number(status.failedCount ?? 0);

        syncIndicator.className = 'badge';

        if (!window.navigator.onLine) {
            syncIndicator.classList.add('badge-dark');
            syncIndicator.textContent = 'Offline';
            return;
        }

        if (conflictCount > 0 || failedCount > 0) {
            syncIndicator.classList.add('badge-danger');
            syncIndicator.textContent = `Sync ${conflictCount + failedCount}`;
            return;
        }

        if (pendingCount > 0) {
            syncIndicator.classList.add('badge-warning');
            syncIndicator.textContent = `Pending ${pendingCount}`;
            return;
        }

        syncIndicator.classList.add('badge-success');
        syncIndicator.textContent = 'Online';
    };

    const fetchSyncStatus = async () => {
        if (statusRequestInFlight) {
            return;
        }

        statusRequestInFlight = true;

        try {
            const response = await fetch(syncConfig.statusUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            renderSyncIndicator(payload.data ?? {});
        } finally {
            statusRequestInFlight = false;
        }
    };

    const runAutoSync = async (force = false) => {
        if (!window.navigator.onLine || syncRequestInFlight) {
            return;
        }

        const now = Date.now();
        const intervalMs = Number(syncConfig.intervalSeconds ?? 60) * 1000;

        if (!force && now - lastAutoRunAt < intervalMs) {
            return;
        }

        syncRequestInFlight = true;
        lastAutoRunAt = now;

        try {
            await fetch(syncConfig.autoUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
                credentials: 'same-origin',
            });
        } finally {
            syncRequestInFlight = false;
            await fetchSyncStatus();
        }
    };

    renderSyncIndicator();
    void fetchSyncStatus();

    window.setInterval(() => {
        void fetchSyncStatus();
    }, 30000);

    window.setInterval(() => {
        void runAutoSync(false);
    }, Number(syncConfig.intervalSeconds ?? 60) * 1000);

    window.addEventListener('online', () => {
        void runAutoSync(true);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            void fetchSyncStatus();
            void runAutoSync(false);
        }
    });
}

if (nativeDesktopConfig) {
    const drawerRoot = document.getElementById('kansor-debugbar-drawer');
    const drawerWebview = document.getElementById('kansor-debugbar-webview');
    const drawerOpenClass = 'kansor-debug-drawer--open';
    const chordDelayMs = 260;
    let chordArmed = false;
    let chordTimerId = null;
    let telescopeRequestInFlight = false;

    const clearChord = () => {
        if (chordTimerId !== null) {
            window.clearTimeout(chordTimerId);
        }

        chordArmed = false;
        chordTimerId = null;
    };

    const ensureDebugbarWebviewLoaded = () => {
        if (!drawerWebview || drawerWebview.getAttribute('src')) {
            return;
        }

        drawerWebview.setAttribute('src', nativeDesktopConfig.debugbarRepoUrl);
    };

    const isDrawerOpen = () => drawerRoot?.classList.contains(drawerOpenClass) ?? false;

    const setDrawerState = (shouldOpen) => {
        if (!drawerRoot) {
            return;
        }

        drawerRoot.classList.toggle(drawerOpenClass, shouldOpen);
        drawerRoot.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');

        if (shouldOpen) {
            ensureDebugbarWebviewLoaded();
        }
    };

    const toggleDrawer = () => {
        setDrawerState(!isDrawerOpen());
    };

    const openTelescopeWindow = async () => {
        if (telescopeRequestInFlight) {
            return;
        }

        telescopeRequestInFlight = true;

        try {
            await fetch(nativeDesktopConfig.telescopeActionUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
                credentials: 'same-origin',
            });
        } finally {
            telescopeRequestInFlight = false;
        }
    };

    document.querySelectorAll('[data-debugbar-dismiss]').forEach((element) => {
        element.addEventListener('click', () => setDrawerState(false));
    });

    document.addEventListener('keydown', (event) => {
        const usesPrimaryModifier = event.ctrlKey || event.metaKey;

        if (!usesPrimaryModifier || !event.shiftKey) {
            return;
        }

        const key = event.key.toLowerCase();

        if (key === 'd' && !event.repeat) {
            event.preventDefault();

            chordArmed = true;
            chordTimerId = window.setTimeout(async () => {
                chordArmed = false;
                chordTimerId = null;

                await openTelescopeWindow();
            }, chordDelayMs);

            return;
        }

        if (key === 'f' && chordArmed) {
            event.preventDefault();
            clearChord();
            toggleDrawer();
        }
    });

    document.addEventListener('keyup', (event) => {
        if (event.key === 'Shift' || event.key === 'Control' || event.key === 'Meta') {
            clearChord();
        }
    });

    window.addEventListener('blur', clearChord);

    if (window.Native?.on) {
        window.Native.on(nativeDesktopConfig.debugbarDrawerEvent, () => {
            toggleDrawer();
        });
    }
}
