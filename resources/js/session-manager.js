// Automatické session management
export function sessionManager() {
    console.log('Session Manager inicializován');
class SessionManager {
    constructor() {
        this.warningShown = false;
        this.checkInterval = 60000; // Kontrola každou minutu
        this.warningTime = 300; // Varování 5 minut před vypršením

        this.startSessionCheck();
    }

    startSessionCheck() {
        setInterval(() => {
            this.checkSession();
        }, this.checkInterval);
    }

    async checkSession() {
        try {
            const response = await fetch('/check-session', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (!data.authenticated) {
                if (data.reason === 'timeout') {
                    alert('Vaše relace vypršela z důvodu nečinnosti. Budete přesměrováni na přihlášení.');
                }
                window.location.href = '/login';
                return;
            }

            // Varování před vypršením
            if (data.remaining_time <= this.warningTime && !this.warningShown) {
                this.showSessionWarning(data.remaining_time);
            }

            // Reset varování pokud je session obnovena
            if (data.remaining_time > this.warningTime) {
                this.warningShown = false;
            }

        } catch (error) {
            console.error('Chyba při kontrole session:', error);
        }
    }

    showSessionWarning(remainingTime) {
        this.warningShown = true;
        const minutes = Math.floor(remainingTime / 60);

        const extend = confirm(`Vaše relace vyprší za ${minutes} minut. Chcete ji prodloužit?`);

        if (extend) {
            this.extendSession();
        }
    }

    async extendSession() {
        try {
            const response = await fetch('/extend-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                this.warningShown = false;
                // Můžete zobrazit toast notifikaci
                console.log('Session prodloužena');
            }
        } catch (error) {
            console.error('Chyba při prodlužování session:', error);
        }
    }
}

// Inicializace při načtení stránky
    const userMeta = document.querySelector('meta[name="user-authenticated"]');
    if (userMeta && userMeta.content === 'true') {
        new SessionManager();
    }
}
