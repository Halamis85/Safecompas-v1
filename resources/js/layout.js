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
            let time;
            const maxIdleTimeMs = 600 * 1000; // 10 minut v milisekundách, musí odpovídat PHP

            // Resetuje časovač aktivity při různých událostech
            document.addEventListener('mousemove', resetTimer);
            document.addEventListener('keydown', resetTimer);
            document.addEventListener('mousedown', resetTimer);
            document.addEventListener('touchstart', resetTimer);
            document.addEventListener('click', resetTimer);
            document.addEventListener('scroll', resetTimer);

            function logout() {
                window.location.href = '/login?timeout=true';
            }

            function resetTimer() {
                clearTimeout(time);
                time = setTimeout(logout, maxIdleTimeMs);
            }

            // Spusťte časovač ihned při inicializaci
            resetTimer();
        };

        // Spusťte funkci po načtení stránky (uvnitř DOMContentLoaded)
        inactivityTime();

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
