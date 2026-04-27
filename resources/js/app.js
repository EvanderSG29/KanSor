import $ from 'jquery';

window.$ = $;
window.jQuery = $;

await import('admin-lte/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js');
await import('admin-lte/dist/js/adminlte.min.js');

const nativeDesktopConfig = window.KanSorNativeDesktop ?? null;

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
