let deferredPrompt = null;

export function registerSW() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
    });

    import('virtual:pwa-register').then(({ registerSW: register }) => {
        register({ immediate: true });
    }).catch(() => {
        navigator.serviceWorker.register('/build/sw.js').catch(() => {});
    });
}

export function getInstallPrompt() {
    return deferredPrompt;
}

export function clearInstallPrompt() {
    deferredPrompt = null;
}
