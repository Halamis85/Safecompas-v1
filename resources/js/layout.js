 function initLayout() {

        // --- Definice proměnných ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const bodyElement = document.getElementById('app-body');

        // --- Kontrola existence klíčových prvků pro téma ---
        if (!themeToggleBtn || !bodyElement) {
            console.warn('layout.js: Tlačítko pro přepínání tématu nebo element body nebyly nalezeny. Ujistěte se, že HTML elementy mají správná ID.');
        } else {
            console.log('layout.js: Tlačítko a body element nalezeny.'); // Nový log
        }

        // --- Funkce pro nastavení tématu ---
        function setTheme(theme) {
            if (bodyElement) { // Vždy kontrolujte, zda element existuje před manipulací
                if (theme === 'dark') {
                    bodyElement.classList.add('dark-mode');
                    bodyElement.setAttribute('data-bs-theme', 'dark');
                    if (themeToggleBtn) { // Zkontrolujte, zda tlačítko existuje
                        themeToggleBtn.innerHTML = '<i class="fa-solid fa-sun"></i> Světlý režim';
                    }
                    localStorage.setItem('theme', 'dark');
                } else {
                    bodyElement.classList.remove('dark-mode');
                    bodyElement.setAttribute('data-bs-theme', 'light');
                    if (themeToggleBtn) { // Zkontrolujte, zda tlačítko existuje
                        themeToggleBtn.innerHTML = '<i class="fa-solid fa-moon"></i> Tmavý režim';
                    }
                    localStorage.setItem('theme', 'light');
                }
            }
        }

        // --- Inicializace tématu při načtení stránky ---
        const savedTheme = localStorage.getItem('theme');
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme) {
            setTheme(savedTheme);
        } else if (prefersDarkScheme) {
            setTheme('dark');
        } else {
            setTheme('light');
        }

        // --- Přepínání tématu po kliknutí ---
        if (themeToggleBtn) { // Přidejte posluchač pouze pokud tlačítko existuje
            themeToggleBtn.addEventListener('click', () => {
                const currentTheme = bodyElement.classList.contains('dark-mode') ? 'dark' : 'light';
                if (currentTheme === 'dark') {
                    setTheme('light');
                } else {
                    setTheme('dark');
                }
            });
        }

        // --- Inactivity Timer logic ---
        // CELÁ tato funkce musí být definována a volána uvnitř DOMContentLoaded.
let inactivityTime = function () {
    // Pouze pro přihlášené uživatele
    const authMeta = document.querySelector('meta[name="user-authenticated"]');
    if (!authMeta || authMeta.content !== 'true') {
        return;
    }

    let logoutTimer;
    let warningTimer;
    let countdownInterval;

    // Čtení hodnoty ze <meta name="session-lifetime"> (v sekundách)
    const metaLifetime = document.querySelector('meta[name="session-lifetime"]');
    const sessionLifetimeSec = metaLifetime
        ? parseInt(metaLifetime.getAttribute('content'), 10)
        : 1800; // fallback 30 min

    // Klient odhlásí o 30s dřív než server (čistý redirect místo 401 chyby)
    const maxIdleTimeMs = Math.max((sessionLifetimeSec - 30) * 1000, 60 * 1000);
    const warningBeforeMs = 2 * 60 * 1000; // varování 2 min před odhlášením
    const warningTimeMs = Math.max(maxIdleTimeMs - warningBeforeMs, 30 * 1000);

    // Reference na DOM elementy modalu
    const warningModalEl = document.getElementById('sessionWarningModal');
    const countdownEl = document.getElementById('sessionCountdown');
    const stayLoggedBtn = document.getElementById('stayLoggedBtn');

    // Bootstrap modal instance (pokud existuje)
    let warningModal = null;
    if (warningModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        warningModal = new bootstrap.Modal(warningModalEl);
    }

    function clearAllTimers() {
        clearTimeout(logoutTimer);
        clearTimeout(warningTimer);
        clearInterval(countdownInterval);
    }

    function logout() {
        clearAllTimers();
        window.location.href = '/login?timeout=true';
    }

    function updateCountdown(seconds) {
        if (!countdownEl) return;
        const mins = Math.floor(Math.max(seconds, 0) / 60);
        const secs = Math.max(seconds, 0) % 60;
        countdownEl.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    function showWarning() {
        if (!warningModal) {
            return; // bez modalu prostě počkáme na logoutTimer
        }

        let remaining = Math.floor(warningBeforeMs / 1000);
        updateCountdown(remaining);
        warningModal.show();

        countdownInterval = setInterval(() => {
            remaining--;
            updateCountdown(remaining);
            if (remaining <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }

    function hideWarning() {
        if (warningModal) {
            warningModal.hide();
        }
        clearInterval(countdownInterval);
    }

    async function extendSession() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch('/extend-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });

            if (response.ok) {
                hideWarning();
                resetTimer();
            } else if (response.status === 401) {
                logout();
            } else {
                console.warn('Extend session vrátil status', response.status);
                logout();
            }
        } catch (e) {
            console.warn('Nepodařilo se prodloužit session:', e);
            logout();
        }
    }

        function resetTimer() {
            clearAllTimers();
            warningTimer = setTimeout(showWarning, warningTimeMs);
            logoutTimer = setTimeout(logout, maxIdleTimeMs);
        }

    // Aktivita resetuje časovač POUZE pokud není zobrazen warning modal
    // (uživatel musí explicitně potvrdit kliknutím)
        function maybeResetTimer() {
            if (warningModalEl && warningModalEl.classList.contains('show')) {
                return;
            }
            resetTimer();
        }

        document.addEventListener('mousemove', maybeResetTimer);
        document.addEventListener('keydown', maybeResetTimer);
        document.addEventListener('mousedown', maybeResetTimer);
        document.addEventListener('touchstart', maybeResetTimer);
        document.addEventListener('click', maybeResetTimer);
        document.addEventListener('scroll', maybeResetTimer);

        if (stayLoggedBtn) {
            stayLoggedBtn.addEventListener('click', extendSession);
        }

        resetTimer();
};

    const toggleButton = document.getElementById('profileSidebarToggle');
    const sidebar = document.getElementById('profileSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeButton = document.getElementById('closeSidebar');

    function openSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.classList.add('no-scroll');
        toggleButton.classList.add('hide-icon'); // Skryjeme tlačítko
        profileSidebarToggle.classList.add('hidden-toggle');
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.classList.remove('no-scroll');
        toggleButton.classList.remove('hide-icon'); // Znovu zobrazíme tlačítko
        profileSidebarToggle.classList.remove('hidden-toggle');
    }

    if (toggleButton && sidebar && overlay && closeButton) {
        toggleButton.addEventListener('click', openSidebar);
        overlay.addEventListener('click', closeSidebar);
        closeButton.addEventListener('click', closeSidebar);
    }
} // ZDE se správně uzavírá export function initLayout()
 document.addEventListener('DOMContentLoaded', initLayout);
 export { initLayout };
