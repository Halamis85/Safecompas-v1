import { Modal, Collapse, Dropdown, Alert } from 'bootstrap';
window.bootstrap = { Modal, Collapse, Dropdown, Alert };

import './layout.js';
import './notifications.js';

// --- Session manager (jen pro přihlášené) ---
const userAuthMeta = document.querySelector('meta[name="user-authenticated"]');
if (userAuthMeta && userAuthMeta.content === 'true') {
    import('./session-manager.js')
        .then(m => m.sessionManager())
        .catch(err => console.error('Chyba při načítání session-manager:', err));
}

// =============================================================================
//  Per-page moduly — vše dynamicky, takže home page nestahuje DataTables
// =============================================================================

const body = document.body;

// Dashboard (úvodní stránka)
if (body.classList.contains('dashboard-home')) {
    Promise.all([
        import('./menuoopp.js'),
        import('./statistika_grafy.js'),
    ])
        .then(([menuModul, statistikaModul]) => {
            menuModul.menuoopp();
            statistikaModul.statik();
        })
        .catch(err => console.error('Chyba při dynamickém importu (dashboard):', err));
}

// Hodiny (canvas)
if (document.getElementById('clock')) {
    import('./clock.js')
        .then(m => m.init())
        .catch(err => console.error('Chyba při načítání clock:', err));
}

// Přehled objednávek
if (body.classList.contains('prehled')) {
    import('./prehledObjednavek.js')
        .then(m => m.prehled())
        .catch(err => console.error('Chyba při načítání prehledObjednavek:', err));
}

// Nová objednávka
if (body.classList.contains('objednavka')) {
    import('./newobjednavka.js')
        .then(m => m.objednavka())
        .catch(err => console.error('Chyba při načítání newobjednavka:', err));
}

// Karta zaměstnance
if (body.classList.contains('karta-zamestnance')) {
    import('./cardEmploee.js')
        .then(m => m.cardEmploee())
        .catch(err => console.error('Chyba při načítání cardEmploee:', err));
}

// Administrace (rozcestník)
if (body.classList.contains('administrace')) {
    import('./administraceAll.js')
        .then(m => m.administraceAll())
        .catch(err => console.error('Chyba při načítání administraceAll:', err));
}

// Lékárničky
if (body.classList.contains('lekarnicke-modul')) {
    import('./lekarnicky.js')
        .then(m => m.lekarnicky())
        .catch(err => console.error('Chyba při načítání lékárniček:', err));
}

// Aktivity uživatelů
if (body.classList.contains('users-activity')) {
    import('./userActivity.js')
        .then(m => m.userActivity())
        .catch(err => console.error('Chyba při načítání userActivity:', err));
}

// Emailové kontakty
if (body.classList.contains('email-contacts')) {
    import('./emailContact.js')
        .then(m => m.emailContact())
        .catch(err => console.error('Chyba při načítání emailContact:', err));
}

// Správa zaměstnanců
if (body.classList.contains('employee-list')) {
    import('./employeeList.js')
        .then(m => m.employeeList())
        .catch(err => console.error('Chyba při načítání employee-list:', err));
}

// Oprávnění
if (body.classList.contains('permissions-admin')) {
    import('./permissions.js')
        .then(m => m.permissions())
        .catch(err => console.error('Chyba při načítání permissions:', err));
}
