import $ from 'jquery';

window.$ = $;
window.jQuery = $;

await import('admin-lte/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js');
await import('admin-lte/dist/js/adminlte.min.js');

const appShellConfig = window.KanSorAppShell ?? null;
const nativeDesktopConfig = window.KanSorNativeDesktop ?? null;
const syncConfig = window.KanSorSync ?? null;

if (appShellConfig) {
    const overlay = document.querySelector('[data-app-shell-overlay]');
    const overlayMessage = document.querySelector('[data-app-shell-message]');
    const backButton = document.querySelector('[data-app-shell-back]');
    const forwardButton = document.querySelector('[data-app-shell-forward]');
    const refreshButton = document.querySelector('[data-app-shell-refresh]');
    const historyKey = 'kansor-shell-history';
    const historyIndexKey = 'kansor-shell-history-index';
    const historyTargetIndexKey = 'kansor-shell-history-target-index';
    const defaultMessage = overlayMessage?.textContent?.trim() || 'Sistem sedang memproses...';

    const normalizeUrl = (url) => {
        const parsedUrl = new URL(url, window.location.origin);
        parsedUrl.hash = '';

        return parsedUrl.toString();
    };

    const readHistoryStack = () => {
        try {
            const rawValue = window.sessionStorage.getItem(historyKey);

            if (! rawValue) {
                return [];
            }

            const parsedValue = JSON.parse(rawValue);

            return Array.isArray(parsedValue) ? parsedValue : [];
        } catch {
            return [];
        }
    };

    const readHistoryIndex = () => {
        try {
            const rawValue = window.sessionStorage.getItem(historyIndexKey);
            const parsedValue = Number.parseInt(rawValue ?? '', 10);

            return Number.isNaN(parsedValue) ? -1 : parsedValue;
        } catch {
            return -1;
        }
    };

    const readTargetIndex = () => {
        try {
            const rawValue = window.sessionStorage.getItem(historyTargetIndexKey);
            const parsedValue = Number.parseInt(rawValue ?? '', 10);

            return Number.isNaN(parsedValue) ? null : parsedValue;
        } catch {
            return null;
        }
    };

    const writeHistoryState = (stack, index) => {
        try {
            window.sessionStorage.setItem(historyKey, JSON.stringify(stack));
            window.sessionStorage.setItem(historyIndexKey, String(index));
        } catch {
            // Ignore sessionStorage write failures and keep the page usable.
        }
    };

    const showLoading = (message = defaultMessage) => {
        if (overlayMessage) {
            overlayMessage.textContent = message;
        }

        if (overlay) {
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
        }

        document.body.classList.add('kansor-shell-busy');
        document.body.setAttribute('aria-busy', 'true');
    };

    const hideLoading = () => {
        if (overlay) {
            overlay.classList.remove('is-visible');
            overlay.setAttribute('aria-hidden', 'true');
        }

        if (overlayMessage) {
            overlayMessage.textContent = defaultMessage;
        }

        document.body.classList.remove('kansor-shell-busy');
        document.body.removeAttribute('aria-busy');
    };

    const currentHistoryState = () => {
        let stack = readHistoryStack();
        let index = readHistoryIndex();
        const currentUrl = normalizeUrl(window.location.href);
        const targetIndex = readTargetIndex();

        if (targetIndex !== null && stack[targetIndex] === currentUrl) {
            index = targetIndex;
            window.sessionStorage.removeItem(historyTargetIndexKey);
        } else {
            const existingIndex = stack.lastIndexOf(currentUrl);

            if (existingIndex >= 0) {
                index = existingIndex;
            } else {
                stack = stack.slice(0, index + 1);
                stack.push(currentUrl);
                index = stack.length - 1;
            }
        }

        writeHistoryState(stack, index);

        return { stack, index };
    };

    const updateNavigationButtons = () => {
        const { stack, index } = currentHistoryState();

        if (backButton) {
            backButton.disabled = index <= 0;
        }

        if (forwardButton) {
            forwardButton.disabled = index < 0 || index >= stack.length - 1;
        }
    };

    const navigateToHistoryIndex = (targetIndex) => {
        const { stack } = currentHistoryState();
        const targetUrl = stack[targetIndex] ?? null;

        if (! targetUrl) {
            updateNavigationButtons();
            return;
        }

        window.sessionStorage.setItem(historyTargetIndexKey, String(targetIndex));
        showLoading('Memuat halaman...');
        window.location.assign(targetUrl);
    };

    const disableSubmitControls = (form) => {
        form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach((element) => {
            element.setAttribute('disabled', 'disabled');
        });
    };

    window.KanSorAppShellUi = {
        showLoading,
        hideLoading,
    };

    currentHistoryState();
    updateNavigationButtons();

    backButton?.addEventListener('click', () => {
        const { index } = currentHistoryState();

        if (index > 0) {
            navigateToHistoryIndex(index - 1);
        }
    });

    forwardButton?.addEventListener('click', () => {
        const { stack, index } = currentHistoryState();

        if (index < stack.length - 1) {
            navigateToHistoryIndex(index + 1);
        }
    });

    refreshButton?.addEventListener('click', () => {
        showLoading('Memuat ulang halaman...');
        window.location.reload();
    });

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

        if (! link || event.defaultPrevented || link.dataset.skipLoading !== undefined) {
            return;
        }

        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        if (link.target && link.target !== '_self') {
            return;
        }

        if (link.hasAttribute('download')) {
            return;
        }

        const href = link.getAttribute('href');

        if (! href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        const destination = new URL(href, window.location.href);
        const current = new URL(window.location.href);

        if (destination.origin !== current.origin) {
            return;
        }

        if (destination.pathname === current.pathname && destination.search === current.search && destination.hash !== '') {
            return;
        }

        showLoading(link.dataset.loadingMessage || 'Memuat halaman...');
    });

    document.addEventListener('submit', (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;

        if (! form) {
            return;
        }

        if (form.dataset.skipDisable === undefined) {
            if (form.dataset.appShellSubmitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.appShellSubmitting = 'true';
            disableSubmitControls(form);
        }

        if (form.dataset.skipLoading === undefined) {
            showLoading(form.dataset.loadingMessage || 'Memproses permintaan...');
        }
    });

    window.addEventListener('pageshow', () => {
        hideLoading();
        updateNavigationButtons();
    });
}

if (syncConfig) {
    const syncIndicator = document.querySelector('[data-sync-indicator]');
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';
    let lastAutoRunAt = 0;
    let statusRequestInFlight = false;
    let syncRequestInFlight = false;

    const renderSyncIndicator = (status = {}) => {
        if (! syncIndicator) {
            return;
        }

        const pendingCount = Number(status.pendingCount ?? 0);
        const queuedCount = Number(status.queuedCount ?? pendingCount);
        const appliedCount = Number(status.lastRun?.summary?.push?.applied ?? status.appliedCount ?? 0);
        const conflictCount = Number(status.conflictCount ?? 0);
        const failedCount = Number(status.failedCount ?? 0);

        syncIndicator.className = 'badge';

        if (! window.navigator.onLine) {
            syncIndicator.classList.add('badge-dark');
            syncIndicator.textContent = 'Offline';
            return;
        }

        if (conflictCount > 0 || failedCount > 0) {
            syncIndicator.classList.add('badge-danger');
            syncIndicator.textContent = `Failed ${conflictCount + failedCount}`;
            return;
        }

        if (queuedCount > 0) {
            syncIndicator.classList.add('badge-warning');
            syncIndicator.textContent = `Queued ${queuedCount}`;
            return;
        }

        if (appliedCount > 0) {
            syncIndicator.classList.add('badge-success');
            syncIndicator.textContent = `Applied ${appliedCount}`;
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

            if (! response.ok) {
                return;
            }

            const payload = await response.json();
            renderSyncIndicator(payload.data ?? {});
        } finally {
            statusRequestInFlight = false;
        }
    };

    const runAutoSync = async (force = false) => {
        if (! window.navigator.onLine || syncRequestInFlight) {
            return;
        }

        const now = Date.now();
        const intervalMs = Number(syncConfig.intervalSeconds ?? 60) * 1000;

        if (! force && now - lastAutoRunAt < intervalMs) {
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
        if (! drawerWebview || drawerWebview.getAttribute('src')) {
            return;
        }

        drawerWebview.setAttribute('src', nativeDesktopConfig.debugbarRepoUrl);
    };

    const isDrawerOpen = () => drawerRoot?.classList.contains(drawerOpenClass) ?? false;

    const setDrawerState = (shouldOpen) => {
        if (! drawerRoot) {
            return;
        }

        drawerRoot.classList.toggle(drawerOpenClass, shouldOpen);
        drawerRoot.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');

        if (shouldOpen) {
            ensureDebugbarWebviewLoaded();
        }
    };

    const toggleDrawer = () => {
        setDrawerState(! isDrawerOpen());
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
                    Accept: 'application/json',
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

        if (! usesPrimaryModifier || ! event.shiftKey) {
            return;
        }

        const key = event.key.toLowerCase();

        if (key === 'd' && ! event.repeat) {
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
