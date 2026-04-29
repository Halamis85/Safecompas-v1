// Periodická kontrola platnosti relace + varování před vypršením.

export function sessionManager() {
    const userMeta = document.querySelector('meta[name="user-authenticated"]');
    if (!userMeta || userMeta.content !== 'true') {
        return;
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        return;
    }
    const csrfToken = csrfMeta.getAttribute('content');

    const CHECK_INTERVAL_MS = 60_000;   // kontrola každou minutu
    const WARNING_SECONDS   = 300;      // varování 5 minut před vypršením

    let warningShown = false;
    let stopped = false;
    let intervalId = null;

    function stop() {
        stopped = true;
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
    }

    async function checkSession() {
        if (stopped) return;

        let response;
        try {
            response = await fetch('/check-session', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });
        } catch (e) {
            // Síťová chyba - tiše ignorovat, zkusíme příště
            return;
        }

        if (response.status === 401) {
            stop();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            return;
        }

        // Pojistka - server nesmí vrátit HTML, ale kdyby ano, neházet syntax error
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            return;
        }

        let data;
        try {
            data = await response.json();
        } catch (e) {
            return;
        }

        if (!data.authenticated) {
            stop();
            window.location.href = '/login';
            return;
        }

        // Backend vrací 'remaining_seconds'
        const remaining = typeof data.remaining_seconds === 'number'
            ? data.remaining_seconds
            : Number.POSITIVE_INFINITY;

        if (remaining <= WARNING_SECONDS && !warningShown) {
            warningShown = true;
            const minutes = Math.max(1, Math.floor(remaining / 60));
            const extend = confirm(
                `Vaše relace vyprší za ${minutes} minut. Chcete ji prodloužit?`
            );
            if (extend) {
                extendSession();
            }
        }

        if (remaining > WARNING_SECONDS) {
            warningShown = false;
        }
    }

    async function extendSession() {
        try {
            const response = await fetch('/extend-session', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data && data.success) {
                warningShown = false;
            }
        } catch (e) {
            // tiché
        }
    }

    intervalId = setInterval(checkSession, CHECK_INTERVAL_MS);
}
