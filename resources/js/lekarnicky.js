import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';
import DataTable from 'datatables.net-dt';
import { initPlanBudovy } from './plan-budovy.js';


export function lekarnicky() {;

    // Globální proměnné
    let appData = {
        lekarnicke: [],
        material: [],
        materialObjednavky: [],
        urazy: [],
        stats: {},
        currentSection: 'prehled',
        filters: {
            search: '',
            status: 'all',
        },
    };
    const appRoot = document.getElementById('lekarnicky-app');
    const pageMode = appRoot?.dataset.pageMode || 'overview';
    const permissions = {
        canManageMaterial: appRoot?.dataset.canManageMaterial === 'true',
        canIssueMaterial: appRoot?.dataset.canIssueMaterial === 'true',
    };

    // CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function normalizeText(value) {
        return String(value ?? '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function formatDate(value, withTime = false) {
        if (!value) return '—';
        const date = new Date(value);
        if (isNaN(date.getTime())) return '—';
        return withTime
            ? date.toLocaleString('cs-CZ')
            : date.toLocaleDateString('cs-CZ');
    }

    function destroyDataTable(tableEl) {
        if (tableEl && DataTable.isDataTable(tableEl)) {
            new DataTable(tableEl).destroy();
        }
    }

    function renderStatusBadge(status) {
        const value = normalizeText(status || 'neznámý');
        const isActive = value === 'aktivni';
        const label = value === 'aktivni' ? 'aktivní' : (status || 'neznámý');
        return `<span class="badge bg-${isActive ? 'success' : 'warning'} ${isActive ? '' : 'text-dark'} shadow-sm">${escapeHtml(label)}</span>`;
    }

    function getMaterialStatus(material) {
        const today = new Date();
        const expirationDate = material.datum_expirace ? new Date(material.datum_expirace) : null;
        const badges = [];
        let rowClass = '';

        if (expirationDate && expirationDate < today) {
            badges.push('<span class="badge bg-danger shadow-sm">Expirováno</span>');
            rowClass = 'row-danger';
        } else if (expirationDate && expirationDate <= new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000)) {
            badges.push('<span class="badge bg-warning shadow-sm text-dark">Brzy expiruje</span>');
            rowClass = 'row-warning';
        }

        if (Number(material.aktualni_pocet) < Number(material.minimalni_pocet)) {
            badges.push('<span class="badge bg-danger shadow-sm">Nízký stav</span>');
            if (!rowClass) rowClass = 'row-warning';
        }

        if (badges.length === 0) {
            badges.push('<span class="badge bg-success shadow-sm">OK</span>');
        }

        return { badges: badges.join(' '), rowClass };
    }

    function getExpirationState(material) {
        const today = new Date();
        const expirationDate = material.datum_expirace ? new Date(material.datum_expirace) : null;

        if (!expirationDate || isNaN(expirationDate.getTime())) {
            return { state: 'ok', label: 'OK' };
        }

        if (expirationDate < today) {
            return { state: 'expired', label: 'EXPIROVÁNO' };
        }

        if (expirationDate <= new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000)) {
            return { state: 'expiring', label: 'EXPIRUJE' };
        }

        return { state: 'ok', label: 'OK' };
    }

    function renderOrderStatus(status) {
        const value = status || 'cekajici';
        const variants = {
            cekajici: ['bg-warning text-dark', 'Čeká'],
            objednano: ['bg-info text-dark', 'Objednáno'],
            vydano: ['bg-primary', 'Předáno'],
            doplneno: ['bg-success', 'Doplněno'],
        };
        const [classes, label] = variants[value] || ['bg-secondary', value];

        return `<span class="badge ${classes} shadow-sm">${escapeHtml(label)}</span>`;
    }

    function renderOrderReason(reason) {
        const labels = {
            manual: 'Ručně',
            expirace: 'Expiruje',
            expirovano: 'Expirováno',
            nizky_stav: 'Nízký stav',
            vydej: 'Po výdeji',
        };

        return labels[reason] || reason || 'Ručně';
    }

    function getLekarnickyCounts(lekarnicky) {
        const material = lekarnicky.material || [];
        return {
            material: material.length,
            expiring: lekarnicky.expirujici_material?.length || material.filter(m => {
                const { rowClass } = getMaterialStatus(m);
                return rowClass === 'row-warning' || rowClass === 'row-danger';
            }).length,
            low: lekarnicky.nizky_stav_material?.length || material.filter(m => Number(m.aktualni_pocet) < Number(m.minimalni_pocet)).length,
            injuries: lekarnicky.urazy?.length || 0,
        };
    }

    function needsAttention(lekarnicky) {
        const counts = getLekarnickyCounts(lekarnicky);
        return counts.expiring > 0 || counts.low > 0 || Boolean(lekarnicky.je_potreba_kontrola);
    }

    function hasItemsNeedingOrder(lekarnicky) {
        if (!lekarnicky.material?.length) return false;
        const warningLimit = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000);
        const activeOrderIds = new Set(
            appData.materialObjednavky
                .filter(o => String(o.lekarnicky_id) === String(lekarnicky.id) &&
                             ['cekajici', 'objednano'].includes(o.status))
                .map(o => String(o.material_id))
        );
        return lekarnicky.material.some(m => {
            const isLow      = Number(m.aktualni_pocet) < Number(m.minimalni_pocet);
            const expDate    = m.datum_expirace ? new Date(m.datum_expirace) : null;
            const isExpiring = expDate && expDate <= warningLimit;
            return (isLow || isExpiring) && !activeOrderIds.has(String(m.id));
        });
    }

    function flattenMaterial() {
        return appData.lekarnicke.flatMap(lekarnicky => (lekarnicky.material || []).map(material => ({
            ...material,
            lekarnicky_id: lekarnicky.id,
            lekarnicky_nazev: lekarnicky.nazev,
            lekarnicky_umisteni: lekarnicky.umisteni,
        })));
    }

    // API helper funkce
    async function apiCall(url, options = {}) {
        // Vždy načítat aktuální CSRF token z DOMu (ne starý z proměnné).
        // Pomáhá to, když Laravel token v průběhu sezení rotuje.
        const currentCsrf = document.querySelector('meta[name="csrf-token"]')?.content
            || csrfToken;

        // Mergování headerů - aby šlo z volání přidat vlastní bez ztráty defaultů
        const mergedHeaders = {
            'Content-Type':     'application/json',
            'Accept':           'application/json',         // ← KLÍČOVÉ: bez tohoto Laravel vrací HTML redirect
            'X-Requested-With': 'XMLHttpRequest',           // další hint pro Laravel, že je to AJAX
            'X-CSRF-TOKEN':     currentCsrf,
            ...(options.headers || {}),
        };

        const finalOptions = {
            ...options,
            headers: mergedHeaders,
            credentials: 'same-origin',
        };

        let response;
        try {
            response = await fetch(url, finalOptions);
        } catch (networkError) {
            // Síťová chyba (offline, server down)
            console.error('API Network Error:', networkError);
            showNotification('Nelze se připojit k serveru. Zkontrolujte připojení.', 'error');
            throw networkError;
        }

        // Session vypršela - přesměrovat na login
        if (response.status === 401) {
            showNotification('Vaše relace vypršela. Budete přesměrováni na přihlášení.', 'error');
            setTimeout(() => { window.location.href = '/login'; }, 1500);
            throw new Error('Session expired');
        }

        // CSRF token mismatch - obvykle expirovaný token, stačí refresh
        if (response.status === 419) {
            showNotification('Bezpečnostní token vypršel. Obnovte prosím stránku (Ctrl+R).', 'error');
            throw new Error('CSRF token mismatch');
        }

        // Pojistka: pokud server omylem vrátí HTML (302 redirect následovaný HTML),
        // čitelná chybová zpráva místo "Unexpected token '<'"
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            console.error('API vrátilo ne-JSON response (content-type=' + contentType + ')');
            showNotification('Server vrátil neočekávanou odpověď. Zkuste se znovu přihlásit.', 'error');
            throw new Error('Unexpected response format');
        }

        let data;
        try {
            data = await response.json();
        } catch (parseError) {
            console.error('API Parse Error:', parseError);
            showNotification('Server vrátil poškozenou odpověď.', 'error');
            throw parseError;
        }

        if (!response.ok) {
            const message = data.message || data.error || `API chyba (${response.status})`;
            showNotification(message, 'error');
            throw new Error(message);
        }

        return data;
    }


    // Načtení dashboardu
    async function loadDashboard() {
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'block';

        try {
            const [data] = await Promise.all([
                apiCall('/api/lekarnicke/dashboard'),
                apiCall('/api/lekarnicke/objednavky-materialu')
                    .then(orders => { appData.materialObjednavky = Array.isArray(orders) ? orders : []; })
                    .catch(() => {}),
            ]);

            appData.lekarnicke = data.lekarnicke || [];
            appData.stats = data.statistiky || {};
            if (data.material) appData.material = data.material;
            if (data.urazy) appData.urazy = data.urazy;

            updateStats();
            showDashboard();
            renderLekarnicke();
            initOrRefreshPlan();
            if (document.getElementById('lekarnicky-material-orders-tbody')) {
                renderMaterialOrdersTable();
            }

        } catch (error) {
            console.error('Chyba při načítání dashboard:', error);
            showNotification('Chyba při načítání dat', 'error');
        } finally {
            if (loadingIndicator) loadingIndicator.style.display = 'none';
        }
    }

    // Plán budovy - inicializace jednou, pak se rerendruje při show.bs.modal
    let planBudovy = null;
    function initOrRefreshPlan() {
        if (!planBudovy) {
            planBudovy = initPlanBudovy({
                apiCall,
                showNotification,
                getLekarnicky: () => appData.lekarnicke,
                openDetailLekarnicky: viewLekarnicky,
            });
        } else if (planBudovy.rerender) {
            planBudovy.rerender();
        }
    }

    // Aktualizace statistik pomocí data-* atributů
    function updateStats() {
        const stats = appData.stats;

        // Používáme data-stat atributy místo ID
        const celkemEl = document.querySelector('[data-stat="celkem"]');
        const aktivniEl = document.querySelector('[data-stat="aktivni"]');
        const expirujiciEl = document.querySelector('[data-stat="expirujici"]');
        const nizkyStavEl = document.querySelector('[data-stat="nizky-stav"]');
        const kontrolaEl = document.querySelector('[data-stat="kontrola"]');
        const urazyEl = document.querySelector('[data-stat="urazy"]');

        if (celkemEl) celkemEl.textContent = stats.celkem_lekarnicek || 0;
        if (aktivniEl) aktivniEl.textContent = stats.aktivni_lekarnicke || 0;
        if (expirujiciEl) expirujiciEl.textContent = stats.expirujici_material || 0;
        if (nizkyStavEl) nizkyStavEl.textContent = stats.nizky_stav_material || 0;
        if (kontrolaEl) kontrolaEl.textContent = stats.potreba_kontroly || 0;
        if (urazyEl) urazyEl.textContent = stats.urazy_tento_mesic || 0;

        const materialMetric = document.querySelector('[data-nav-metric="material"]');
        const urazyMetric = document.querySelector('[data-nav-metric="urazy"]');
        if (materialMetric) {
            materialMetric.textContent = appData.lekarnicke.reduce((sum, l) => sum + (l.material?.length || 0), 0);
        }
        if (urazyMetric) {
            urazyMetric.textContent = stats.urazy_tento_mesic || 0;
        }
    }

    // Zobrazení dashboardu
    function showDashboard() {
        const dashboardStats = document.getElementById('dashboard-stats');
        const navigationCards = document.getElementById('navigation-cards');
        const heroSection = document.getElementById('lekarnicky-hero-section');

        // Odebereme inline display:none — CSS třídy (grid/row) se postarají o layout.
        if (dashboardStats) dashboardStats.style.display = '';
        if (navigationCards) navigationCards.style.display = '';
        if (heroSection) heroSection.style.display = '';
    }

    // Zobrazení sekce
    function showSection(section) {
        appData.currentSection = section;

        // Plán budovy - otevři modal
        if (section === 'plan') {
            const modalEl = document.getElementById('planBudovyModal');
            if (!modalEl) return;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            // Init pokud ještě nebyl
            initOrRefreshPlan();
            modal.show();
            return;
        }

        // Materiál - modal
        if (section === 'material') {
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('materialModalList'));
            modal.show();
            loadMaterial();
            return;
        }

        // Úrazy - modal
        if (section === 'urazy') {
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('urazyModalList'));
            modal.show();
            loadUrazy();
            return;
        }

        // Výkazy - modal
        if (section === 'vykazy') {
            const modalElement = document.getElementById('vykazyModalList');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modalElement.addEventListener('shown.bs.modal', function onShown() {
                setupVykazy();
                modalElement.removeEventListener('shown.bs.modal', onShown);
            }, { once: true });
            modal.show();
            return;
        }
    }

    // Vykreslení lékárniček
    function renderLekarnicke() {
        const container = document.getElementById('lekarnicke-list');
        if (!container) return;

        container.innerHTML = '';

        const filteredLekarnicke = appData.lekarnicke.filter(lekarnicky => {
            const search = appData.filters.search;
            const haystack = [
                lekarnicky.nazev,
                lekarnicky.umisteni,
                lekarnicky.zodpovedna_osoba,
                lekarnicky.popis,
            ].map(normalizeText).join(' ');
            const matchesSearch = !search || haystack.includes(search);
            const status = normalizeText(lekarnicky.status);
            const matchesStatus = appData.filters.status === 'all'
                || (appData.filters.status === 'attention' && needsAttention(lekarnicky))
                || status === appData.filters.status;

            return matchesSearch && matchesStatus;
        });

        if (filteredLekarnicke.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="lekarnicky-empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <strong>Žádné lékárničky k zobrazení</strong>
                        <span>Zkuste upravit filtr nebo hledaný text.</span>
                    </div>
                </div>
            `;
            return;
        }

        filteredLekarnicke.forEach(lekarnicky => {
            const card = createLekarnickCard(lekarnicky);
            container.appendChild(card);
        });
    }

    // Vytvoření karty lékárničky
    function createLekarnickCard(lekarnicky) {
        const counts      = getLekarnickyCounts(lekarnicky);
        const attention   = needsAttention(lekarnicky);
        const needsOrder  = hasItemsNeedingOrder(lekarnicky);
        const nextInspection = formatDate(lekarnicky.dalsi_kontrola);
        const isOk = !attention;

        // Alert pilulky — jen zobrazené problémy
        const alertPills = [
            counts.expiring > 0
                ? `<span class="lk-pill lk-pill-warning"><i class="fa-solid fa-hourglass-half"></i>${counts.expiring} expiruje</span>`
                : '',
            counts.low > 0
                ? `<span class="lk-pill lk-pill-danger"><i class="fa-solid fa-arrow-down-short-wide"></i>${counts.low} nízký stav</span>`
                : '',
            lekarnicky.je_potreba_kontrola
                ? `<span class="lk-pill lk-pill-info"><i class="fa-solid fa-calendar-check"></i>nutná kontrola</span>`
                : '',
            isOk
                ? `<span class="lk-pill lk-pill-ok"><i class="fa-solid fa-circle-check"></i>vše v pořádku</span>`
                : '',
        ].filter(Boolean).join('');

        // Akční tlačítka
        const actionButtons = [
            `<button class="btn btn-outline-primary btn-sm" data-action="view-lekarnicky" data-id="${lekarnicky.id}">
                <i class="fa-solid fa-eye me-1"></i>Přehled
            </button>`,
            permissions.canIssueMaterial && pageMode === 'overview'
                ? `<button class="btn btn-outline-secondary btn-sm" data-action="vydat-material" data-id="${lekarnicky.id}">
                    <i class="fa-solid fa-hand-holding-medical me-1"></i>Vydat
                  </button>`
                : '',
            (permissions.canManageMaterial || permissions.canIssueMaterial) && pageMode === 'overview'
                ? `<button class="btn btn-outline-success btn-sm" data-action="doplnit-material" data-id="${lekarnicky.id}">
                    <i class="fa-solid fa-box-open me-1"></i>Doplnit
                  </button>`
                : '',
            (permissions.canManageMaterial || permissions.canIssueMaterial) && pageMode === 'overview' && needsOrder
                ? `<button class="btn btn-warning btn-sm" data-action="objednat-expirujici" data-id="${lekarnicky.id}">
                    <i class="fa-solid fa-cart-plus me-1"></i>Objednat
                  </button>`
                : '',
            pageMode === 'overview'
                ? `<button class="btn btn-outline-success btn-sm" data-action="kontrola-lekarnicky" data-id="${lekarnicky.id}">
                    <i class="fa-solid fa-check me-1"></i>Kontrola 
                  </button>`
                : '',
        ].filter(Boolean).join('');

        const cardDiv = document.createElement('div');
        cardDiv.className = 'col-lg-4 col-md-6';
        cardDiv.innerHTML = `
            <div class="lk-card ${attention ? 'lk-card-attention' : ''}">
                <div class="lk-card-body">

                    <div class="lk-card-header">
                        <div class="lk-card-icon">
                            <i class="fa-solid fa-kit-medical"></i>
                        </div>
                        <div class="lk-card-identity">
                            <h6>${escapeHtml(lekarnicky.nazev)}</h6>
                            <span><i class="fa-solid fa-location-dot"></i>${escapeHtml(lekarnicky.umisteni || 'Bez umístění')}</span>
                        </div>
                        ${renderStatusBadge(lekarnicky.status)}
                    </div>

                    <div class="lk-card-meta">
                        <span><i class="fa-solid fa-user-shield"></i>${escapeHtml(lekarnicky.zodpovedna_osoba || 'Bez zodpovědné osoby')}</span>
                        <span class="${lekarnicky.je_potreba_kontrola ? 'lk-meta-warn' : ''}">
                            <i class="fa-regular fa-calendar-days"></i>Kontrola provedena: ${nextInspection}
                        </span>
                    </div>

                    <div class="lk-alert-pills">
                        ${alertPills}
                    </div>

                    <div class="lk-card-metrics">
                        <div class="lk-metric">
                            <span>Materiál</span>
                            <strong>${counts.material}</strong>
                        </div>
                        <div class="lk-metric ${counts.expiring > 0 ? 'lk-metric-warn' : ''}">
                            <span>Expiruje</span>
                            <strong>${counts.expiring}</strong>
                        </div>
                        <div class="lk-metric ${counts.low > 0 ? 'lk-metric-danger' : ''}">
                            <span>Nízký stav</span>
                            <strong>${counts.low}</strong>
                        </div>
                        <div class="lk-metric lk-metric-info">
                            <span>Úrazy</span>
                            <strong>${counts.injuries}</strong>
                        </div>
                    </div>

                </div>
                <div class="lk-card-footer">
                    ${actionButtons}
                </div>
            </div>
        </div>
        `;

        return cardDiv;
    }

    // Načtení materiálu
    async function loadMaterial() {
        try {
            // Načtení dat bez rekurze
            const data = await apiCall('/api/lekarnicke/dashboard');
            appData.lekarnicke = data.lekarnicke || [];
            appData.stats = data.statistiky || {};

            updateMaterialFilter();
            renderMaterialTable();
        } catch (e) {
            console.error("Chyba při aktualizaci materiálu", e);
        }
    }

    // Aktualizace filtru lékárniček
    function updateMaterialFilter() {
        const select = document.getElementById('material-lekarnicky-filter');
        if (!select) return;

        select.innerHTML = '<option value="">Všechny lékárničky</option>';

        appData.lekarnicke.forEach(lekarnicky => {
            const option = document.createElement('option');
            option.value = lekarnicky.id;
            option.textContent = lekarnicky.nazev;
            select.appendChild(option);
        });
    }

    // Vykreslení tabulky materiálu
    function renderMaterialTable() {
        const tbody = document.getElementById('material-tbody');
        if (!tbody) return;

        const materialTableEl = document.getElementById('materialTable');

        // Zrušit starou instanci DataTables (pokud existuje).
        // V "datatables.net-dt" je isDataTable statická metoda na importu.
        if (materialTableEl && DataTable.isDataTable(materialTableEl)) {
            new DataTable(materialTableEl).destroy();
        }

        // Vyčistit tbody před vykreslením nových řádků
        tbody.innerHTML = '';

        // Získání všech materiálů ze všech lékárniček
        const allMaterial = flattenMaterial();

        if (allMaterial.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted">Žádný materiál nenalezen</td>
                </tr>
            `;
            return;
        }

        allMaterial.forEach(material => {
            const row = createMaterialRow(material);
            tbody.appendChild(row);
        });

        // Inicializace DataTable nad vyplněnou tabulkou
        if (materialTableEl) {
            new DataTable(materialTableEl, {
                responsive: true,
                pageLength: 25,
                language: { url: '/assets/cs.json' },
            });
        }
    }

    // Vytvoření řádku materiálu
    function createMaterialRow(material) {
        const expirationDate = material.datum_expirace ? new Date(material.datum_expirace) : null;
        const { badges, rowClass } = getMaterialStatus(material);

        const expirationText = expirationDate ?
            expirationDate.toLocaleDateString('cs-CZ') :
            'Není stanovena';

        const row = document.createElement('tr');
        row.className = rowClass;
        row.innerHTML = `
            <td><span class="fw-bold text-primary">${escapeHtml(material.lekarnicky_nazev)}</span></td>
            <td>${escapeHtml(material.nazev_materialu)}</td>
            <td><small class="text-muted text-uppercase">${escapeHtml(material.typ_materialu)}</small></td>
            <td class="text-center"><span class="badge bg-info text-dark">${escapeHtml(material.aktualni_pocet)} ${escapeHtml(material.jednotka)}</span></td>
            <td class="text-center"><small class="text-muted">${escapeHtml(material.minimalni_pocet)} ${escapeHtml(material.jednotka)}</small></td>
            <td><i class="fa-regular fa-calendar-alt me-1"></i> ${expirationText}</td>
            <td class="text-center">${badges}</td>
            <td class="text-end px-4">
                ${permissions.canManageMaterial ? `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-link text-primary p-0 me-3" data-action="edit-material" data-id="${material.id}">
                            <i class="fa-solid fa-pen-to-square fs-5"></i>
                        </button>
                        <button class="btn btn-link text-danger p-0" data-action="delete-material" data-id="${material.id}">
                            <i class="fa-solid fa-trash-can fs-5"></i>
                        </button>
                    </div>
                ` : '<span class="text-muted">—</span>'}
            </td>
        `;

        return row;
    }

    // Načtení úrazů
    async function loadUrazy() {
        const tableEl = document.getElementById('urazyTable');
        if (!tableEl) return;

        // Zničit starou instanci, pokud existuje
        if (DataTable.isDataTable(tableEl)) {
            new DataTable(tableEl).destroy();
            // Vyčistit tbody, aby si DataTable nesahal na staré řádky
            const oldTbody = tableEl.querySelector('tbody');
            if (oldTbody) oldTbody.innerHTML = '';
        }

        let urazy = [];
        try {
            const response = await apiCall('/api/lekarnicke/urazy');
            urazy = Array.isArray(response) ? response : [];
            appData.urazy = urazy;
        } catch (error) {
            console.error('Chyba při načítání úrazů:', error);
            urazy = []; // i při chybě vykreslíme prázdnou tabulku, ne placeholder s colspan
        }

        const zavaznostBadges = {
            'lehky':   '<span class="badge bg-success shadow-sm">Lehký</span>',
            'stredni': '<span class="badge bg-warning shadow-sm text-dark">Střední</span>',
            'tezky':   '<span class="badge bg-danger shadow-sm">Těžký</span>',
        };

        // Inicializace DataTable s daty - DataTable si řídí render řádků sám
        new DataTable(tableEl, {
            data: urazy,
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            language: { url: '/assets/cs.json' },
            columns: [
                {
                    title: 'Datum',
                    data: 'datum_cas_urazu',
                    render: function (data) {
                        if (!data) return '—';
                        const d = new Date(data);
                        if (isNaN(d.getTime())) return '—';
                        return '<i class="fa-regular fa-clock me-1 text-muted"></i> '
                             + d.toLocaleString('cs-CZ');
                    },
                },
                {
                    title: 'Zaměstnanec',
                    data: 'zamestnanec',
                    render: function (data) {
                        if (!data) return '—';
                        const jmeno = `${data.prijmeni || ''} ${data.jmeno || ''}`.trim();
                        return jmeno
                            ? `<span class="fw-bold">${escapeHtml(jmeno)}</span>`
                            : '—';
                    },
                },
                {
                    title: 'Místo úrazu',
                    data: 'misto_urazu',
                    render: (data) => escapeHtml(data || '—'),
                },
                {
                    title: 'Závažnost',
                    data: 'zavaznost',
                    render: (data) => zavaznostBadges[data] || escapeHtml(data || '—'),
                },
                {
                    title: 'Lékárnička',
                    data: 'lekarnicky',
                    render: function (data) {
                        if (!data || !data.nazev) return '—';
                        return `<span class="text-primary">${escapeHtml(data.nazev)}</span>`;
                    },
                },
                {
                    title: 'Akce',
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-end px-4',
                    render: function (id) {
                        return `
                            <button class="btn btn-link text-danger p-0"
                                    data-action="delete-uraz" data-id="${id}"
                                    title="Smazat záznam">
                                <i class="fa-solid fa-trash-can fs-5"></i>
                            </button>
                        `;
                    },
                },
            ],
        });
    }

    // ========== OPRAVENÉ MODAL FUNKCE ==========

    // Zobrazení notifikace (stejný jako váš stávající systém)
    function showNotification(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' :
            type === 'error' ? 'alert-danger' : 'alert-info';

        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '2000';
            document.body.appendChild(container);
        }

        container.appendChild(notification);

        // Automatické skrytí po 5 sekundách
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    // ========== POMOCNÉ FUNKCE ==========

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        bootstrap.Modal.getOrCreateInstance(modal).hide();
    }

    function populateLekarnickySelect(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;
        // Zachováme první "placeholder" <option> a přidáme zbytek
        const placeholder = select.querySelector('option[value=""]');
        select.innerHTML = '';
        if (placeholder) select.appendChild(placeholder);

        appData.lekarnicke.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = `${l.nazev} (${l.umisteni})`;
            select.appendChild(opt);
        });
    }

    async function populateZamestnanciSelect(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;
        try {
            const data = await apiCall('/api/lekarnicke/zamestnanci');
            const placeholder = select.querySelector('option[value=""]');
            select.innerHTML = '';
            if (placeholder) select.appendChild(placeholder);

            data.forEach(z => {
                const opt = document.createElement('option');
                opt.value = z.id;
                opt.textContent = `${z.prijmeni} ${z.jmeno}${z.stredisko ? ' — ' + z.stredisko : ''}`;
                select.appendChild(opt);
            });
        } catch (error) {
            console.error('Chyba při načítání zaměstnanců:', error);
        }
    }

    // Naplní select kandidáty na vlastníka lékárničky.
    async function populateOwnersSelect(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;

        // Zachovat první "-- vyberte --" option, ostatní vyhodit
        select.innerHTML = '<option value="">-- vyberte uživatele --</option>';

        try {
            const users = await apiCall('/api/lekarnicke/available-owners');

            if (!Array.isArray(users) || users.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.disabled = true;
                opt.textContent = '(žádný kandidát - přiřaďte oprávnění lekarnicke.create nebo .edit)';
                select.appendChild(opt);
                return;
            }

            users.forEach(user => {
                const opt = document.createElement('option');
                opt.value = user.id;
                opt.textContent = user.email
                    ? `${user.label} (${user.email})`
                    : user.label;
                select.appendChild(opt);
            });
        } catch (error) {
            console.error('Chyba při načítání kandidátů na vlastníka:', error);
            showNotification('Nepodařilo se načíst seznam uživatelů', 'error');
        }
    }

    function resetMaterialForm() {
        const form = document.getElementById('material-form');
        if (form) form.reset();
        const idEl = document.getElementById('material-id');
        if (idEl) idEl.value = '';
        const titleEl = document.getElementById('materialModalTitle');
        if (titleEl) titleEl.textContent = 'Přidat materiál';
        const submitBtn = document.getElementById('material-submit-btn');
        if (submitBtn) submitBtn.textContent = 'Uložit';
    }

    function populateDoplnitModal(selectedLekarnickyId = '') {
        const lekarnickySelect = document.getElementById('doplnit-lekarnicky-select');
        const materialSelect = document.getElementById('doplnit-material-select');
        const materialIdInput = document.getElementById('doplnit-material-id');
        const objednavkaIdInput = document.getElementById('doplnit-objednavka-id');
        const stockInput = document.getElementById('doplnit-current-stock');
        const quantityInput = document.getElementById('doplnit-quantity');

        if (!lekarnickySelect || !materialSelect || !materialIdInput || !objednavkaIdInput) return;

        lekarnickySelect.innerHTML = '<option value="">Všechny lékárničky</option>';
        appData.lekarnicke.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = `${l.nazev} (${l.umisteni || 'bez umístění'})`;
            lekarnickySelect.appendChild(opt);
        });
        lekarnickySelect.value = selectedLekarnickyId ? String(selectedLekarnickyId) : '';

        const renderMaterials = () => {
            const selectedId = lekarnickySelect.value;

            const deliveredOrders = appData.materialObjednavky
                .filter(order => order.status === 'vydano' &&
                    order.material_id &&
                    (!selectedId || String(order.lekarnicky_id) === String(selectedId)));

            const materialById = new Map(flattenMaterial().map(m => [String(m.id), m]));

            materialSelect.innerHTML = '<option value="">-- vyberte předanou objednávku --</option>';

            if (deliveredOrders.length === 0) {
                const empty = document.createElement('option');
                empty.value = '';
                empty.disabled = true;
                empty.textContent = selectedId
                    ? '(žádný dodaný materiál čekající na doplnění)'
                    : '(vyberte lékárničku nebo nejsou dodané objednávky)';
                materialSelect.appendChild(empty);
            } else {
                deliveredOrders.forEach(order => {
                    const m = materialById.get(String(order.material_id));
                    const opt = document.createElement('option');
                    opt.value = order.material_id;
                    opt.dataset.orderId = order.id;
                    opt.dataset.quantity = order.mnozstvi || 1;
                    opt.dataset.stock = m?.aktualni_pocet ?? order.material?.aktualni_pocet ?? '';
                    opt.dataset.unit = m?.jednotka || order.jednotka || '';
                    opt.textContent = `${order.nazev_materialu || m?.nazev_materialu || 'Materiál'} · ${order.lekarnicky?.nazev || m?.lekarnicky_nazev || 'Lékárnička'} · předáno ${order.mnozstvi} ${order.jednotka || m?.jednotka || ''}`;
                    materialSelect.appendChild(opt);
                });
            }

            materialIdInput.value = '';
            objednavkaIdInput.value = '';
            if (stockInput) stockInput.value = '—';
            if (quantityInput) quantityInput.value = '';
        };

        renderMaterials();
        lekarnickySelect.onchange = renderMaterials;
        materialSelect.onchange = () => {
            const opt = materialSelect.selectedOptions[0];
            materialIdInput.value = materialSelect.value || '';
            objednavkaIdInput.value = opt?.dataset.orderId || '';
            if (quantityInput) quantityInput.value = opt?.dataset.quantity || '';
            if (stockInput) {
                stockInput.value = opt?.dataset.stock
                    ? `${opt.dataset.stock} ${opt.dataset.unit || ''}`.trim()
                    : '—';
            }
        };
    }

    async function openDoplnitMaterialForLekarnicky(id) {
        const modalEl = document.getElementById('doplnitMaterialModal');
        const form = document.getElementById('doplnit-material-form');
        if (!modalEl || !form) return;

        form.reset();
        await loadMaterialOrders({ silent: true });
        populateDoplnitModal(id);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function populateVydejModal(selectedLekarnickyId = '') {
        const lekarnickySelect = document.getElementById('vydej-lekarnicky-select');
        const materialTbody = document.getElementById('vydej-material-tbody');
        const materialIdInput = document.getElementById('vydej-material-id');
        const urazSelect = document.getElementById('vydej-uraz-select');
        const stockInput = document.getElementById('vydej-material-stock');
        const selectedMaterial = document.getElementById('vydej-selected-material');
        const selectedLocation = document.getElementById('vydej-selected-location');

        if (!lekarnickySelect || !materialTbody || !materialIdInput || !urazSelect) return;

        lekarnickySelect.innerHTML = '<option value="">Všechny lékárničky</option>';
        appData.lekarnicke.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = `${l.nazev} (${l.umisteni || 'bez umístění'})`;
            lekarnickySelect.appendChild(opt);
        });
        lekarnickySelect.value = selectedLekarnickyId ? String(selectedLekarnickyId) : '';

        const renderMaterials = () => {
            const selectedId = lekarnickySelect.value;
            const material = flattenMaterial()
                .filter(m => !selectedId || String(m.lekarnicky_id) === String(selectedId))
                .filter(m => Number(m.aktualni_pocet) > 0)
                .sort((a, b) => {
                    const order = { expired: 0, expiring: 1, ok: 2 };
                    return order[getExpirationState(a).state] - order[getExpirationState(b).state]
                        || String(a.nazev_materialu || '').localeCompare(String(b.nazev_materialu || ''), 'cs');
                });

            materialIdInput.value = '';
            if (stockInput) stockInput.value = '—';
            if (selectedMaterial) selectedMaterial.textContent = 'Zatím není vybráno';
            if (selectedLocation) selectedLocation.textContent = 'Vyberte materiál z tabulky.';

            if (material.length === 0) {
                materialTbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Žádný dostupný materiál k výdeji</td>
                    </tr>
                `;
                return;
            }

            materialTbody.innerHTML = material.map(m => {
                const expirationState = getExpirationState(m);
                const statusBadge = expirationState.state === 'expired'
                    ? '<span class="badge bg-danger shadow-sm">Expirováno</span>'
                    : expirationState.state === 'expiring'
                        ? '<span class="badge bg-warning text-dark shadow-sm">Expiruje</span>'
                        : '<span class="badge bg-success shadow-sm">OK</span>';

                const hasActiveOrder = appData.materialObjednavky.some(order =>
                    String(order.material_id) === String(m.id) &&
                    ['cekajici', 'objednano'].includes(order.status)
                );

                const objednatBtn = hasActiveOrder
                    ? `<button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Již objednáno">
                           <i class="fa-solid fa-check me-1"></i>Objednáno
                       </button>`
                    : `<button type="button"
                               class="btn btn-outline-warning"
                               data-action="objednat-material"
                               data-id="${m.id}">
                           Objednat
                       </button>`;

                return `
                    <tr class="vydej-material-row ${expirationState.state === 'expired' ? 'row-danger' : expirationState.state === 'expiring' ? 'row-warning' : ''}">
                        <td>
                            <span class="fw-bold">${escapeHtml(m.nazev_materialu || '—')}</span>
                            <small class="d-block text-muted">${escapeHtml(m.typ_materialu || '—')}</small>
                        </td>
                        <td>${escapeHtml(m.lekarnicky_nazev || '—')}</td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark">${escapeHtml(m.aktualni_pocet)} ${escapeHtml(m.jednotka || '')}</span>
                        </td>
                        <td>${m.datum_expirace ? formatDate(m.datum_expirace) : '—'}</td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Akce materiálu">
                                <button type="button"
                                        class="btn btn-primary"
                                        data-action="select-vydej-material"
                                        data-id="${m.id}">
                                    Vydat
                                </button>
                                ${objednatBtn}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        renderMaterials();
        lekarnickySelect.onchange = renderMaterials;

        try {
            const urazy = await apiCall('/api/lekarnicke/urazy');
            appData.urazy = Array.isArray(urazy) ? urazy : [];
            urazSelect.innerHTML = '<option value="">Bez záznamu úrazu</option>';
            appData.urazy.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                const zamestnanec = u.zamestnanec
                    ? `${u.zamestnanec.prijmeni || ''} ${u.zamestnanec.jmeno || ''}`.trim()
                    : 'bez zaměstnance';
                opt.textContent = `${formatDate(u.datum_cas_urazu, true)} · ${zamestnanec} · ${u.misto_urazu || 'bez místa'}`;
                urazSelect.appendChild(opt);
            });
        } catch (error) {
            console.error('Chyba při načítání úrazů pro výdej:', error);
        }
    }

    function selectVydejMaterial(materialId) {
        const material = flattenMaterial().find(m => String(m.id) === String(materialId));
        if (!material) return;

        const materialIdInput = document.getElementById('vydej-material-id');
        const stockInput = document.getElementById('vydej-material-stock');
        const selectedMaterial = document.getElementById('vydej-selected-material');
        const selectedLocation = document.getElementById('vydej-selected-location');
        const quantityInput = document.querySelector('#vydej-material-form [name="vydane_mnozstvi"]');

        if (materialIdInput) materialIdInput.value = material.id;
        if (stockInput) stockInput.value = `${material.aktualni_pocet} ${material.jednotka || ''}`.trim();
        if (selectedMaterial) selectedMaterial.textContent = material.nazev_materialu || 'Vybraný materiál';
        if (selectedLocation) {
            const expiration = material.datum_expirace ? ` · expirace ${formatDate(material.datum_expirace)}` : '';
            selectedLocation.textContent = `${material.lekarnicky_nazev || 'Lékárnička'} · ${material.lekarnicky_umisteni || 'bez umístění'}${expiration}`;
        }
        if (quantityInput) {
            quantityInput.max = material.aktualni_pocet;
            quantityInput.value = '1';
            quantityInput.focus();
        }

        document.querySelectorAll('#vydej-material-tbody tr').forEach(row => row.classList.remove('is-selected'));
        document.querySelector(`#vydej-material-tbody [data-action="select-vydej-material"][data-id="${material.id}"]`)
            ?.closest('tr')
            ?.classList.add('is-selected');
    }

    async function openVydejMaterialForLekarnicky(id = '') {
        const modalEl = document.getElementById('vydejMaterialModal');
        const form = document.getElementById('vydej-material-form');
        if (!modalEl || !form) return;

        form.reset();
        await loadMaterialOrders({ silent: true });
        await populateVydejModal(id);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function loadMaterialOrders({ silent = false } = {}) {
        try {
            const orders = await apiCall('/api/lekarnicke/objednavky-materialu');
            appData.materialObjednavky = Array.isArray(orders) ? orders : [];
            renderMaterialOrdersTable();
            return appData.materialObjednavky;
        } catch (error) {
            console.error('Chyba při načítání objednávek materiálu:', error);
            if (!silent) {
                showNotification('Nepodařilo se načíst objednávky materiálu', 'error');
            }
            return [];
        }
    }

    function renderMaterialOrdersTable() {
        const tbody = document.getElementById('lekarnicky-material-orders-tbody');
        const tableEl = document.getElementById('lekarnickyMaterialOrdersTable');
        if (!tbody) return;

        destroyDataTable(tableEl);

        const showVydane = document.getElementById('show-vydane-orders')?.checked ?? false;
        const orders = showVydane
            ? appData.materialObjednavky
            : appData.materialObjednavky.filter(o => o.status !== 'doplneno');

        if (orders.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Zatím nejsou žádné objednávky materiálu.</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = orders.map(order => {
            const lekarnickyName = order.lekarnicky?.nazev || 'Lékárnička';
            const lekarnickyLocation = order.lekarnicky?.umisteni || 'bez umístění';
            const material = order.material;
            const stock = material
                ? `${material.aktualni_pocet} ${material.jednotka || order.jednotka || ''}`.trim()
                : 'položka smazána';

            return `
                <tr>
                    <td>
                        <span class="fw-bold">${escapeHtml(order.nazev_materialu || '—')}</span>
                        <small class="d-block text-muted">${escapeHtml(order.typ_materialu || '—')} · skladem ${escapeHtml(stock)}</small>
                    </td>
                    <td>
                        ${escapeHtml(lekarnickyName)}
                        <small class="d-block text-muted">${escapeHtml(lekarnickyLocation)}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info text-dark">${escapeHtml(order.mnozstvi)} ${escapeHtml(order.jednotka || '')}</span>
                    </td>
                    <td>${escapeHtml(renderOrderReason(order.duvod))}</td>
                    <td class="text-center">${renderOrderStatus(order.status)}</td>
                    <td>${formatDate(order.datum_objednani, true)}</td>
                    <td class="text-end px-4">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Akce objednávky">
                            <button type="button"
                                    class="btn btn-outline-info"
                                    data-action="material-order-status"
                                    data-status="objednano"
                                    data-id="${order.id}"
                                    ${order.status !== 'cekajici' ? 'disabled' : ''}>
                                Objednáno
                            </button>
                            <button type="button"
                                    class="btn btn-outline-success"
                                    data-action="material-order-status"
                                    data-status="vydano"
                                    data-id="${order.id}"
                                    ${order.status === 'vydano' || order.status === 'doplneno' ? 'disabled' : ''}>
                                Předáno
                            </button>
                            <button type="button"
                                    class="btn btn-outline-danger"
                                    data-action="delete-material-order"
                                    data-id="${order.id}">
                                Smazat
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (tableEl && orders.length > 0) {
            new DataTable(tableEl, {
                language: { url: '/assets/cs.json' },
                order: [[5, 'desc']],
                pageLength: 10,
                responsive: true,
            });
        }
    }

    async function objednatMaterial(materialId, duvod = 'manual') {
        if (!materialId) return;

        try {
            const result = await apiCall('/api/lekarnicke/objednavky-materialu', {
                method: 'POST',
                body: JSON.stringify({ material_id: materialId, duvod }),
            });

            if (result.success) {
                showNotification(result.message || 'Položka byla objednána', 'success');
                await loadMaterialOrders({ silent: true });
            }
        } catch (error) {
            console.error('Chyba při objednání materiálu:', error);
        }
    }

    async function objednatExpirujiciMaterial(lekarnickyId) {
        if (!lekarnickyId) return;

        try {
            const result = await apiCall(`/api/lekarnicke/${lekarnickyId}/objednat-expirujici`, {
                method: 'POST',
                body: JSON.stringify({}),
            });

            if (result.success) {
                showNotification(result.message || 'Expirující položky byly přidány do objednávek', 'success');
                await loadMaterialOrders({ silent: true });
            }
        } catch (error) {
            console.error('Chyba při objednání expirujících položek:', error);
        }
    }

    async function updateMaterialOrderStatus(orderId, status) {
        try {
            const result = await apiCall(`/api/lekarnicke/objednavky-materialu/${orderId}/status`, {
                method: 'PATCH',
                body: JSON.stringify({ status }),
            });

            if (result.success) {
                showNotification(result.message || 'Objednávka aktualizována', 'success');
                await loadMaterialOrders();
            }
        } catch (error) {
            console.error('Chyba při změně statusu objednávky:', error);
        }
    }

    async function deleteMaterialOrder(orderId) {
        if (!confirm('Opravdu smazat tuto objednávku materiálu?')) return;

        try {
            const result = await apiCall(`/api/lekarnicke/objednavky-materialu/${orderId}`, {
                method: 'DELETE',
            });

            if (result.success) {
                showNotification(result.message || 'Objednávka byla smazána', 'success');
                await loadMaterialOrders();
            }
        } catch (error) {
            console.error('Chyba při mazání objednávky materiálu:', error);
        }
    }

    // ========== HANDLERY MODALŮ ==========

    // Handler pro přidání i editaci materiálu (rozliší podle hidden material_id)
    async function handleMaterialSubmit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        const materialId = data.material_id || '';
        const lekarnickyId = data.lekarnicky_id;

        // Vyčisti prázdné nepovinné hodnoty, ať validátor nespadne
        ['datum_expirace', 'cena_za_jednotku', 'dodavatel', 'poznamky']
            .forEach(k => { if (data[k] === '') delete data[k]; });
        delete data.material_id;

        try {
            let result;
            if (materialId) {
                // EDITACE
                result = await apiCall(`/api/lekarnicke/material/${materialId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            } else {
                // VYTVOŘENÍ
                result = await apiCall(`/api/lekarnicke/${lekarnickyId}/material`, {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            }

            if (result.success) {
                showNotification(result.message || 'Uloženo', 'success');
                closeModal('addMaterialModal');
                resetMaterialForm();
                await loadDashboard();
                // Pokud je seznam materiálu otevřený, překreslíme i jeho tabulku.
                if (document.getElementById('material-tbody')) {
                    renderMaterialTable();
                }
            } else {
                showNotification(result.message || 'Chyba při ukládání', 'error');
            }
        } catch (error) {
            console.error('Chyba při ukládání materiálu:', error);
        }
    }

    // Handler pro záznam úrazu
    async function handleAddUraz(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        // Checkbox: převést na boolean
        data.prevezen_do_nemocnice = formData.has('prevezen_do_nemocnice') ? 1 : 0;

        // datetime-local -> format MySQL
        if (data.datum_cas_urazu) {
            data.datum_cas_urazu = data.datum_cas_urazu.replace('T', ' ') + ':00';
        }

        try {
            const result = await apiCall('/api/lekarnicke/urazy', {
                method: 'POST',
                body: JSON.stringify(data)
            });

            if (result.success) {
                showNotification(result.message || 'Úraz zaznamenán', 'success');
                closeModal('addUrazModal');
                e.target.reset();
                await loadDashboard();
            } else {
                showNotification(result.message || 'Chyba při ukládání', 'error');
            }
        } catch (error) {
            console.error('Chyba při záznamu úrazu:', error);
        }
    }

    async function handleVydejMaterialSubmit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        if (!data.material_id) {
            showNotification('Vyberte prosím materiál z tabulky.', 'warning');
            return;
        }

        ['uraz_id', 'poznamky'].forEach(k => { if (data[k] === '') delete data[k]; });

        try {
            const result = await apiCall('/api/lekarnicke/vydej', {
                method: 'POST',
                body: JSON.stringify(data),
            });

            if (result.success) {
                showNotification(result.message || 'Materiál byl vydán', 'success');
                closeModal('vydejMaterialModal');
                e.target.reset();
                await loadDashboard();
                await loadMaterialOrders({ silent: true });
                if (document.getElementById('material-tbody')) {
                    renderMaterialTable();
                }
            } else {
                showNotification(result.message || 'Výdej se nepodařilo uložit', 'error');
            }
        } catch (error) {
            console.error('Chyba při výdeji materiálu:', error);
        }
    }

    async function handleDoplnitMaterialSubmit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        const materialId = data.material_id;

        if (!materialId) {
            showNotification('Vyberte prosím existující materiál k doplnění.', 'warning');
            return;
        }

        ['datum_expirace', 'poznamky']
            .forEach(k => { if (data[k] === '') delete data[k]; });
        delete data.material_id;

        try {
            const result = await apiCall(`/api/lekarnicke/material/${materialId}/doplnit`, {
                method: 'POST',
                body: JSON.stringify(data),
            });

            if (result.success) {
                showNotification(result.message || 'Materiál byl doplněn', 'success');
                closeModal('doplnitMaterialModal');
                e.target.reset();
                await loadDashboard();
                await loadMaterialOrders({ silent: true });
                if (document.getElementById('material-tbody')) {
                    renderMaterialTable();
                }
            } else {
                showNotification(result.message || 'Doplnění se nepodařilo uložit', 'error');
            }
        } catch (error) {
            console.error('Chyba při doplnění materiálu:', error);
        }
    }

    // Vykreslí obsah detail modalu a otevře ho
    function openDetailLekarnicky(data) {
        const materialTableEl = document.getElementById('detailMaterialTable');
        const urazyTableEl = document.getElementById('detailUrazyTable');
        const counts = getLekarnickyCounts(data);

        document.getElementById('detail-lekarnicky-title').innerHTML = `<i class="fa-solid fa-kit-medical me-2 text-primary"></i>${escapeHtml(data.nazev || 'Lékárnička')}`;
        document.getElementById('detail-umisteni').textContent = data.umisteni || '—';
        document.getElementById('detail-zodpovedna').textContent = data.zodpovedna_osoba || '—';
        document.getElementById('detail-status').innerHTML = renderStatusBadge(data.status);
        document.getElementById('detail-posledni').textContent = formatDate(data.posledni_kontrola);
        document.getElementById('detail-dalsi').textContent = formatDate(data.dalsi_kontrola);
        document.getElementById('detail-popis').textContent = data.popis || '—';
        document.getElementById('detail-material-count').textContent = counts.material;
        document.getElementById('detail-expiring-count').textContent = counts.expiring;
        document.getElementById('detail-low-count').textContent = counts.low;
        document.getElementById('detail-urazy-count').textContent = data.urazy?.length || 0;

        destroyDataTable(materialTableEl);
        destroyDataTable(urazyTableEl);

        const materialTbody = document.getElementById('detail-material-tbody');
        const urazyTbody = document.getElementById('detail-urazy-tbody');
        if (materialTbody) materialTbody.innerHTML = '';
        if (urazyTbody) urazyTbody.innerHTML = '';

        let materialDt = null;
        let urazyDt = null;

        if (materialTableEl) {
            materialDt = new DataTable(materialTableEl, {
                data: data.material || [],
                responsive: true,
                pageLength: 5,
                lengthChange: false,
                order: [[4, 'asc']],
                language: {
                    url: '/assets/cs.json',
                    emptyTable: 'Žádný materiál',
                },
                columns: [
                    {
                        title: 'Název',
                        data: 'nazev_materialu',
                        render: (value) => `<span class="fw-bold">${escapeHtml(value || '—')}</span>`,
                    },
                    {
                        title: 'Typ',
                        data: 'typ_materialu',
                        render: (value) => `<small class="text-muted text-uppercase">${escapeHtml(value || '—')}</small>`,
                    },
                    {
                        title: 'Stav',
                        data: null,
                        className: 'text-center',
                        render: (row) => `<span class="badge bg-info text-dark">${escapeHtml(row.aktualni_pocet ?? '—')} ${escapeHtml(row.jednotka || '')}</span>`,
                    },
                    {
                        title: 'Min / Max',
                        data: null,
                        className: 'text-center',
                        render: (row) => `${escapeHtml(row.minimalni_pocet ?? '—')} / ${escapeHtml(row.maximalni_pocet ?? '—')}`,
                    },
                    {
                        title: 'Expirace',
                        data: 'datum_expirace',
                        render: (value) => `<i class="fa-regular fa-calendar-alt me-1 text-muted"></i>${formatDate(value)}`,
                    },
                    {
                        title: 'Status',
                        data: null,
                        className: 'text-center',
                        orderable: false,
                        render: (row) => getMaterialStatus(row).badges,
                    },
                ],
                createdRow: function (row, rowData) {
                    const { rowClass } = getMaterialStatus(rowData);
                    if (rowClass) row.classList.add(rowClass);
                },
            });
        }

        const zavaznostBadges = {
            'lehky':   '<span class="badge bg-success shadow-sm">Lehký</span>',
            'stredni': '<span class="badge bg-warning shadow-sm text-dark">Střední</span>',
            'tezky':   '<span class="badge bg-danger shadow-sm">Těžký</span>',
        };

        if (urazyTableEl) {
            urazyDt = new DataTable(urazyTableEl, {
                data: data.urazy || [],
                responsive: true,
                pageLength: 5,
                lengthChange: false,
                order: [[0, 'desc']],
                language: {
                    url: '/assets/cs.json',
                    emptyTable: 'Žádný úraz',
                },
                columns: [
                    {
                        title: 'Datum',
                        data: 'datum_cas_urazu',
                        render: (value) => `<i class="fa-regular fa-clock me-1 text-muted"></i>${formatDate(value, true)}`,
                    },
                    {
                        title: 'Zaměstnanec',
                        data: 'zamestnanec',
                        render: function (value) {
                            if (!value) return '—';
                            const name = `${value.prijmeni || ''} ${value.jmeno || ''}`.trim();
                            return name ? `<span class="fw-bold">${escapeHtml(name)}</span>` : '—';
                        },
                    },
                    {
                        title: 'Místo',
                        data: 'misto_urazu',
                        render: (value) => escapeHtml(value || '—'),
                    },
                    {
                        title: 'Závažnost',
                        data: 'zavaznost',
                        render: (value) => zavaznostBadges[value] || escapeHtml(value || '—'),
                    },
                ],
            });
        }

        const firstTab = document.getElementById('detail-prehled-tab');
        if (firstTab) {
            bootstrap.Tab.getOrCreateInstance(firstTab).show();
        }

        document.querySelectorAll('#detailLekarnickyTabs [data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                materialDt?.columns.adjust();
                urazyDt?.columns.adjust();
            }, { once: true });
        });

        const modalEl = document.getElementById('detailLekarnickyModal');
        modalEl.addEventListener('shown.bs.modal', function onShown() {
            materialDt?.columns.adjust();
            urazyDt?.columns.adjust();
            modalEl.removeEventListener('shown.bs.modal', onShown);
        });
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    // ========== AKCE NAD ZÁZNAMY ==========

    async function viewLekarnicky(id) {
        try {
            const data = await apiCall(`/api/lekarnicke/${id}`);
            openDetailLekarnicky(data);
        } catch (error) {
            console.error('Chyba při načítání detailu:', error);
        }
    }

    async function kontrolaLekarnicky(id) {
        if (!confirm('Opravdu chcete zaznamenat kontrolu této lékárničky?')) {
            return;
        }

        try {
            const result = await apiCall(`/api/lekarnicke/${id}/kontrola`, {
                method: 'POST'
            });

            if (result.success) {
                showNotification(result.message, 'success');
                await loadDashboard();
            }
        } catch (error) {
            console.error('Chyba při kontrole lékárničky:', error);
        }
    }

    async function editMaterial(id) {
        // Najdi materiál v appData
        let material = null;
        let lekarnickyId = null;
        for (const l of appData.lekarnicke) {
            const found = (l.material || []).find(m => m.id == id);
            if (found) { material = found; lekarnickyId = l.id; break; }
        }
        if (!material) {
            showNotification('Materiál nebyl nalezen v aktuálních datech', 'error');
            return;
        }

        // Otevři modal
        const modalEl = document.getElementById('addMaterialModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Počkej na plnění selectu (to řeší show.bs.modal), pak naplň hodnoty
        modalEl.addEventListener('shown.bs.modal', function once() {
            modalEl.removeEventListener('shown.bs.modal', once);

            const form = document.getElementById('material-form');
            form.reset();
            document.getElementById('material-id').value = material.id;
            document.getElementById('materialModalTitle').textContent = 'Upravit materiál';
            document.getElementById('material-submit-btn').textContent = 'Uložit změny';

            form.querySelector('[name="lekarnicky_id"]').value = lekarnickyId;
            form.querySelector('[name="nazev_materialu"]').value = material.nazev_materialu || '';
            form.querySelector('[name="typ_materialu"]').value = material.typ_materialu || '';
            form.querySelector('[name="aktualni_pocet"]').value = material.aktualni_pocet ?? '';
            form.querySelector('[name="minimalni_pocet"]').value = material.minimalni_pocet ?? '';
            form.querySelector('[name="maximalni_pocet"]').value = material.maximalni_pocet ?? '';
            form.querySelector('[name="jednotka"]').value = material.jednotka || '';
            form.querySelector('[name="datum_expirace"]').value = material.datum_expirace ? material.datum_expirace.substring(0,10) : '';
            form.querySelector('[name="cena_za_jednotku"]').value = material.cena_za_jednotku ?? '';
            form.querySelector('[name="dodavatel"]').value = material.dodavatel || '';
            form.querySelector('[name="poznamky"]').value = material.poznamky || '';
        });

        modal.show();
    }

    async function deleteMaterial(id) {
        if (!confirm('Opravdu chcete smazat tento materiál?')) {
            return;
        }

        try {
            const result = await apiCall(`/api/lekarnicke/material/${id}`, {
                method: 'DELETE'
            });

            if (result.success) {
                showNotification(result.message, 'success');
                await loadDashboard();
                if (document.getElementById('material-tbody')) {
                    renderMaterialTable();
                }
            }
        } catch (error) {
            console.error('Chyba při mazání materiálu:', error);
        }
    }

    async function deleteUraz(id) {
        if (!confirm('Opravdu chcete smazat tento záznam úrazu?')) return;

        try {
            const result = await apiCall(`/api/lekarnicke/urazy/${id}`, {
                method: 'DELETE'
            });
            if (result.success) {
                showNotification(result.message || 'Záznam smazán', 'success');
                await loadUrazy();
                await loadDashboard();
            }
        } catch (error) {
            console.error('Chyba při mazání úrazu:', error);
        }
    }

    // ========== EVENT LISTENERY ==========

    // Event listenery
    function setupEventListeners() {
        // Delegace pro karty lékárniček
        const lekarnickeList = document.getElementById('lekarnicke-list');
        if (lekarnickeList) {
            lekarnickeList.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                const id = parseInt(btn.dataset.id);
                if (btn.dataset.action === 'view-lekarnicky') viewLekarnicky(id);
                if (btn.dataset.action === 'kontrola-lekarnicky') kontrolaLekarnicky(id);
                if (btn.dataset.action === 'material-lekarnicky') openMaterialForLekarnicky(id);
                if (btn.dataset.action === 'doplnit-material') openDoplnitMaterialForLekarnicky(id);
                if (btn.dataset.action === 'vydat-material') openVydejMaterialForLekarnicky(id);
                if (btn.dataset.action === 'objednat-expirujici') objednatExpirujiciMaterial(id);
            });
        }

        // Delegace pro tabulku materiálu (modal)
        const materialModal = document.getElementById('materialModalList');
        if (materialModal) {
            materialModal.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                const id = parseInt(btn.dataset.id);
                if (btn.dataset.action === 'edit-material') editMaterial(id);
                if (btn.dataset.action === 'delete-material') deleteMaterial(id);
            });
        }

        const vydejModal = document.getElementById('vydejMaterialModal');
        if (vydejModal) {
            vydejModal.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                if (btn.dataset.action === 'select-vydej-material') selectVydejMaterial(btn.dataset.id);
                if (btn.dataset.action === 'objednat-material') objednatMaterial(btn.dataset.id, 'manual');
            });
        }

        const materialOrdersBoard = document.getElementById('lekarnicky-orders-board');
        if (materialOrdersBoard) {
            materialOrdersBoard.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                if (btn.dataset.action === 'refresh-material-orders') loadMaterialOrders();
                if (btn.dataset.action === 'material-order-status') updateMaterialOrderStatus(btn.dataset.id, btn.dataset.status);
                if (btn.dataset.action === 'delete-material-order') deleteMaterialOrder(btn.dataset.id);
            });
        }

        const showVydaneToggle = document.getElementById('show-vydane-orders');
        if (showVydaneToggle) {
            showVydaneToggle.addEventListener('change', () => renderMaterialOrdersTable());
        }

        // Delegace pro tabulku úrazů (modal) — funguje i po překreslení DataTables
        const urazyModal = document.getElementById('urazyModalList');
        if (urazyModal) {
            urazyModal.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action="delete-uraz"]');
                if (btn) deleteUraz(parseInt(btn.dataset.id));
            });
        }

        // Navigace mezi sekcemi
        document.querySelectorAll('.navigation-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const section = e.currentTarget.getAttribute('data-section');
                showSection(section);
            });
        });

        document.querySelectorAll('[data-action="open-vydej-material"]').forEach(button => {
            button.addEventListener('click', () => openVydejMaterialForLekarnicky());
        });

        // Globální objednávka všeho k expiraci
        const globalOrderBtn = document.querySelector('[data-action="objednat-expirujici-all"]');
        if (globalOrderBtn) {
            globalOrderBtn.addEventListener('click', () => {
                const expiringKits = appData.lekarnicke.filter(l => getLekarnickyCounts(l).expiring > 0);
                if (expiringKits.length === 0) {
                    showNotification('Žádný materiál k expiraci nenalezen.', 'info');
                    return;
                }
                
                if (confirm(`Opravdu chcete objednat veškerý expirující materiál pro ${expiringKits.length} lékárniček?`)) {
                    // Implementace hromadné objednávky by volala API, 
                    // pro teď můžeme simulovat nebo volat stávající funkci pro každou lékárničku
                    expiringKits.forEach(l => objednatExpirujiciMaterial(l.id));
                    showNotification('Hromadná objednávka byla spuštěna.', 'success');
                }
            });
        }

        // Filter materiálu podle lékárničky
        const materialFilter = document.getElementById('material-lekarnicky-filter');
        if (materialFilter) {
            materialFilter.addEventListener('change', (e) => {
                filterMaterial(e.target.value);
            });
        }

        const lekarnickySearch = document.getElementById('lekarnicky-search');
        if (lekarnickySearch) {
            lekarnickySearch.addEventListener('input', (e) => {
                appData.filters.search = normalizeText(e.target.value.trim());
                renderLekarnicke();
            });
        }

        const lekarnickyStatusFilter = document.getElementById('lekarnicky-status-filter');
        if (lekarnickyStatusFilter) {
            lekarnickyStatusFilter.addEventListener('change', (e) => {
                appData.filters.status = e.target.value;
                renderLekarnicke();
            });
        }

        // Form submit pro přidání lékárničky
        const addLekarnickForm = document.getElementById('add-lekarnicky-form');
        if (addLekarnickForm) {
            addLekarnickForm.addEventListener('submit', handleAddLekarnicky);
        }

        // Form submit pro přidání / editaci materiálu
        const materialForm = document.getElementById('material-form');
        if (materialForm) {
            materialForm.addEventListener('submit', handleMaterialSubmit);
        }

        const vydejMaterialForm = document.getElementById('vydej-material-form');
        if (vydejMaterialForm) {
            vydejMaterialForm.addEventListener('submit', handleVydejMaterialSubmit);
        }

        const doplnitMaterialForm = document.getElementById('doplnit-material-form');
        if (doplnitMaterialForm) {
            doplnitMaterialForm.addEventListener('submit', handleDoplnitMaterialSubmit);
        }

        // Form submit pro záznam úrazu
        const urazForm = document.getElementById('uraz-form');
        if (urazForm) {
            urazForm.addEventListener('submit', handleAddUraz);
        }

        // Před otevřením modalu materiálu naplníme seznam lékárniček
        const addMaterialModal = document.getElementById('addMaterialModal');
        if (addMaterialModal) {
            addMaterialModal.addEventListener('show.bs.modal', (e) => {
                // Pokud se modal otevírá tlačítkem "Přidat materiál", zresetujeme formulář
                const button = e.relatedTarget;
                if (button && button.getAttribute('data-bs-target') === '#addMaterialModal') {
                    resetMaterialForm();
                }
                populateLekarnickySelect('material-lekarnicky-select');
            });
        }

        // Před otevřením modalu úrazu načteme zaměstnance i lékárničky
        const addUrazModal = document.getElementById('addUrazModal');
        if (addUrazModal) {
            addUrazModal.addEventListener('show.bs.modal', () => {
                populateLekarnickySelect('uraz-lekarnicky-select');
                populateZamestnanciSelect('uraz-zamestnanec-select');
            });
        }

        // Event listener pro zodpovědná osoba za lékarniku
        const addLekarnickModal = document.getElementById('addLekarnickModal');
        if (addLekarnickModal) {
            addLekarnickModal.addEventListener('show.bs.modal', () => {
                populateOwnersSelect('lekarnicky-owner-select');
            });
        }
    }

    // Filter materiálu
    function filterMaterial(lekarnicky_id) {
        const tableEl = document.getElementById('materialTable');

        if (!tableEl || !DataTable.isDataTable(tableEl)) return;
        const dataTable = new DataTable(tableEl);
        if (!lekarnicky_id) {
            dataTable.column(0).search('').draw();
            return;
        }

        const selectedLekarnicky = appData.lekarnicke.find(l => l.id == lekarnicky_id);
        if (!selectedLekarnicky) return;

        dataTable.column(0).search(selectedLekarnicky.nazev || '').draw();
    }

    async function openMaterialForLekarnicky(id) {
        const modalEl = document.getElementById('materialModalList');
        if (!modalEl) return;

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        await loadMaterial();

        const materialFilter = document.getElementById('material-lekarnicky-filter');
        if (materialFilter) {
            materialFilter.value = String(id);
        }
        filterMaterial(id);
    }

    // ========== VÝKAZY (statistiky + grafy + export) ==========

    function setupVykazy() {
        const miniCardsContainer = document.getElementById('stats-mini-cards');
        if (!miniCardsContainer) return;

        const stats = appData.stats;

        // Vykreslení horních karet
        miniCardsContainer.innerHTML = `
            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-20">
                    <div class="small text-white-50 text-uppercase fw-bold mb-1">Lékárničky</div>
                    <div class="h3 fw-bold text-white mb-0">${stats.celkem_lekarnicek || 0}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-20">
                    <div class="small text-white-50 text-uppercase fw-bold mb-1">Expirace</div>
                    <div class="h3 fw-bold text-warning mb-0">${stats.expirujici_material || 0}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-20">
                    <div class="small text-white-50 text-uppercase fw-bold mb-1">Nízký stav</div>
                    <div class="h3 fw-bold text-danger mb-0">${stats.nizky_stav_material || 0}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-20">
                    <div class="small text-white-50 text-uppercase fw-bold mb-1">Úrazy / měsíc</div>
                    <div class="h3 fw-bold text-info mb-0">${stats.urazy_tento_mesic || 0}</div>
                </div>
            </div>
        `;

        // Předvyplnění období pro export (od 1. dne aktuálního měsíce do dnes)
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const exportOdEl = document.getElementById('export-od');
        const exportDoEl = document.getElementById('export-do');
        if (exportOdEl) exportOdEl.value = firstDay.toISOString().split('T')[0];
        if (exportDoEl) exportDoEl.value = today.toISOString().split('T')[0];

        // Inicializace grafů
        renderCharts();

        // Event listener pro export (přepisujeme onclick, ne addEventListener,
        // aby se netvořilo víc handlerů při opakovaném otevření modalu)
        const exportBtn = document.getElementById('export-vykaz');
        if (exportBtn) {
            exportBtn.onclick = async () => {
                const od = document.getElementById('export-od').value;
                const doDate = document.getElementById('export-do').value;
                if (!od || !doDate) {
                    showNotification('Vyberte prosím období pro export', 'warning');
                    return;
                }
                showNotification('Generuji výkaz, prosím čekejte...', 'info');
                window.location.href = `/api/lekarnicke/export?od=${od}&do=${doDate}`;
            };
        }
    }

    let charts = {}; // Uložiště pro instance grafů pro pozdější zničení

    async function renderCharts() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js není načteno!');
            return;
        }

        try {
            // Načtení reálných dat z backendu
            const statsData = await apiCall('/api/lekarnicke/stats');

            // Barvy pro grafy
            const colors = {
                primary: '#0d6efd',
                danger: '#dc3545',
                warning: '#ffc107',
                info: '#0dcaf0',
                text: '#e2e8f0'
            };

            // Zničit staré grafy pokud existují
            Object.values(charts).forEach(c => c.destroy());
            charts = {};

            // 1. Trend úrazů (Line Chart)
            const ctxInjuries = document.getElementById('injuriesChart');
            if (ctxInjuries) {
                charts.injuries = new Chart(ctxInjuries, {
                    type: 'line',
                    data: {
                        labels: statsData.injuries.map(i => i.label),
                        datasets: [{
                            label: 'Počet úrazů',
                            data: statsData.injuries.map(i => i.count),
                            borderColor: colors.info,
                            backgroundColor: 'rgba(13, 202, 240, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: colors.text, stepSize: 1 } },
                            x: { grid: { display: false }, ticks: { color: colors.text } }
                        }
                    }
                });
            }

            // 2. Stav materiálu (Doughnut Chart)
            const ctxMaterial = document.getElementById('materialStatusChart');
            if (ctxMaterial) {
                charts.material = new Chart(ctxMaterial, {
                    type: 'doughnut',
                    data: {
                        labels: ['V pořádku', 'Nízký stav', 'Expirováno'],
                        datasets: [{
                            data: [
                                statsData.materials.ok,
                                statsData.materials.low,
                                statsData.materials.expired
                            ],
                            backgroundColor: [colors.primary, colors.warning, colors.danger],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: colors.text, padding: 20 } }
                        },
                        cutout: '70%'
                    }
                });
            }

            // 3. Kontroly (Bar Chart) - Pending vs Done
            const ctxInspections = document.getElementById('inspectionsChart');
            if (ctxInspections) {
                charts.inspections = new Chart(ctxInspections, {
                    type: 'bar',
                    data: {
                        labels: ['V pořádku', 'Nutná kontrola'],
                        datasets: [{
                            label: 'Lékárničky',
                            data: [statsData.inspections.done, statsData.inspections.pending],
                            backgroundColor: [colors.primary, colors.danger],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: colors.text, stepSize: 1 } },
                            y: { grid: { display: false }, ticks: { color: colors.text } }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Chyba při načítání dat pro grafy:', error);
            showNotification('Nepodařilo se načíst data pro grafy', 'error');
        }
    }

    // ========== HANDLER PRO PŘIDÁNÍ LÉKÁRNIČKY ==========

    async function handleAddLekarnicky(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        try {
            const result = await apiCall('/api/lekarnicke', {
                method: 'POST',
                body: JSON.stringify(data)
            });

            if (result.success) {
                closeModal('addLekarnickModal');
                e.target.reset();
                showNotification(result.message || 'Lékárnička byla úspěšně přidána', 'success');
                await loadDashboard();

            } else {
                showNotification(result.message || 'Chyba při ukládání', 'error');
            }
        } catch (error) {
            console.error('Chyba při přidávání lékárničky:', error);
            showNotification('Chyba při komunikaci se serverem: ' + error.message, 'error');
        }
    }

    // ========== INICIALIZACE ==========

    setupEventListeners();
    loadDashboard();
}
