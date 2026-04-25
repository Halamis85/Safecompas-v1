// resources/js/lekarnicky.js - OPRAVENÁ VERZE
import * as bootstrap from 'bootstrap';

export function lekarnicky() {
    console.log('Lékárničky modul inicializován');

    // Globální proměnné
    let appData = {
        lekarnicke: [],
        material: [],
        urazy: [],
        stats: {},
        currentSection: 'prehled'
    };

    // CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // API helper funkce
    async function apiCall(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        };

        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'API chyba');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            showNotification('Chyba při komunikaci se serverem: ' + error.message, 'error');
            throw error;
        }
    }

    // Načtení dashboardu
    async function loadDashboard() {
        const loadingIndicator = document.getElementById('loading-indicator');
        loadingIndicator.style.display = 'block';

        try {
            const data = await apiCall('/api/lekarnicke/dashboard');

            appData.lekarnicke = data.lekarnicke || [];
            appData.stats = data.statistiky || {};
            // Pokud API vrací i materiál a úrazy, uložíme je
            if (data.material) appData.material = data.material;
            if (data.urazy) appData.urazy = data.urazy;

            updateStats();
            showDashboard();
            // loadCurrentSection(); // ODSTRANĚNO PRO ZABRÁNĚNÍ REKURZE

        } catch (error) {
            console.error('Chyba při načítání dashboard:', error);
            showNotification('Chyba při načítání dat', 'error');
        } finally {
            loadingIndicator.style.display = 'none';
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
    }

    // Zobrazení dashboardu
    function showDashboard() {
        const dashboardStats = document.getElementById('dashboard-stats');
        const navigationCards = document.getElementById('navigation-cards');

        if (dashboardStats) dashboardStats.style.display = 'flex';
        if (navigationCards) navigationCards.style.display = 'flex';
    }

    // Zobrazení sekce
    function showSection(section) {
        appData.currentSection = section;

        // Zobrazit vybranou sekci nebo otevřít modal
        if (section === 'material') {
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('materialModalList'));
            modal.show();
            loadMaterial();
            return;
        }

        if (section === 'urazy') {
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('urazyModalList'));
            modal.show();
            loadUrazy();
            return;
        }

        if (section === 'vykazy') {
            const modalElement = document.getElementById('vykazyModalList');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            
            // Grafy se musí inicializovat až když je modal viditelný
            modalElement.addEventListener('shown.bs.modal', function onShown() {
                setupVykazy();
                modalElement.removeEventListener('shown.bs.modal', onShown);
            }, { once: true });
            
            modal.show();
            return;
        }

        // Skrýt všechny sekce (pouze pokud přepínáme inline sekce)
        document.querySelectorAll('.content-section').forEach(el => {
            el.style.display = 'none';
        });

        const targetSection = document.getElementById(`section-${section}`);
        if (targetSection) {
            targetSection.style.display = 'block';
        }

        // Označit aktivní navigaci
        document.querySelectorAll('.navigation-card').forEach(el => {
            el.classList.remove('active');
        });

        const activeCard = document.querySelector(`[data-section="${section}"]`);
        if (activeCard) {
            activeCard.classList.add('active');
        }

        // Načíst data pro sekci
        loadCurrentSection();
    }

    // Načtení dat aktuální sekce
    function loadCurrentSection() {
        switch (appData.currentSection) {
            case 'prehled':
                renderLekarnicke();
                break;
            case 'material':
                loadMaterial();
                break;
            case 'urazy':
                loadUrazy();
                break;
            case 'vykazy':
                setupVykazy();
                break;
        }
    }

    // Vykreslení lékárniček
    function renderLekarnicke() {
        const container = document.getElementById('lekarnicke-list');
        if (!container) return;

        container.innerHTML = '';

        if (appData.lekarnicke.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center">
                    <p class="text-muted">Žádné lékárničky nenalezeny</p>
                </div>
            `;
            return;
        }

        appData.lekarnicke.forEach(lekarnicky => {
            const card = createLekarnickCard(lekarnicky);
            container.appendChild(card);
        });
    }

    // Vytvoření karty lékárničky
    function createLekarnickCard(lekarnicky) {
        const statusClass = lekarnicky.status === 'aktivni' ? 'success' : 'warning';
        const expirujiciCount = lekarnicky.expirujici_material?.length || 0;
        const nizkyStavCount = lekarnicky.nizky_stav_material?.length || 0;

        const cardDiv = document.createElement('div');
        cardDiv.className = 'col-md-4 mb-3';
        cardDiv.innerHTML = `
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">${lekarnicky.nazev}</h6>
                    <span class="badge bg-${statusClass}">${lekarnicky.status}</span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        <i class="fa-solid fa-location-dot"></i> ${lekarnicky.umisteni}
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fa-solid fa-user"></i> ${lekarnicky.zodpovedna_osoba}
                    </p>

                    <div class="row text-center">
                        <div class="col">
                            <small class="text-muted">Materiály</small>
                            <div class="fw-bold">${lekarnicky.material?.length || 0}</div>
                        </div>
                        <div class="col">
                            <small class="text-warning">Expirují</small>
                            <div class="fw-bold text-warning">${expirujiciCount}</div>
                        </div>
                        <div class="col">
                            <small class="text-danger">Nízký stav</small>
                            <div class="fw-bold text-danger">${nizkyStavCount}</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewLekarnicky(${lekarnicky.id})">
                            <i class="fa-solid fa-eye"></i> Detail
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="kontrolaLekarnicky(${lekarnicky.id})">
                            <i class="fa-solid fa-check"></i> Kontrola
                        </button>
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

    // Vykreslení tabulky materiálu
    function renderMaterialTable() {
        const tbody = document.getElementById('material-tbody');
        if (!tbody) return;

        // Zrušit starou instanci DataTables (pokud existuje)
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#materialTable')) {
            $('#materialTable').DataTable().destroy();
            $(tbody).empty();
        }

        const materialList = appData.lekarnicke.flatMap(l => 
            (l.material || []).map(m => ({ ...m, lekarnicky_nazev: l.nazev }))
        );


        // Kontrola existence souboru s českým překladem
        const tableConfig = {
            responsive: true,
            pageLength: 25
        };

        // Přidat český překlad pokud existuje
        if (typeof $ !== 'undefined') {
            $.ajax({
                url: '/assets/cs.json',
                async: false,
                success: function() {
                    tableConfig.language = { url: '/assets/cs.json' };
                },
                error: function() {
                    // Český překlad není k dispozici, použít výchozí
                    console.log('Český překlad pro DataTables není k dispozici');
                }
            });
        }

        if ($.fn.DataTable) {
            $('#materialTable').DataTable(tableConfig);
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

        // Zrušit starou instanci DataTables (pokud existuje)
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#materialTable')) {
            $('#materialTable').DataTable().destroy();
            $(tbody).empty();
        }

        tbody.innerHTML = '';

        // Získání všech materiálů ze všech lékárniček
        const allMaterial = [];
        appData.lekarnicke.forEach(lekarnicky => {
            if (lekarnicky.material) {
                lekarnicky.material.forEach(material => {
                    allMaterial.push({
                        ...material,
                        lekarnicky_nazev: lekarnicky.nazev
                    });
                });
            }
        });

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

        // Inicializace DataTable
        if ($.fn.DataTable) {
            const tableConfig = {
                responsive: true,
                pageLength: 25,
                language: { url: '/assets/cs.json' }
            };
            $('#materialTable').DataTable(tableConfig);
        }
    }

    // Vytvoření řádku materiálu
    function createMaterialRow(material) {
        const today = new Date();
        const expirationDate = material.datum_expirace ? new Date(material.datum_expirace) : null;

        let statusBadge = '<span class="badge bg-success">OK</span>';
        let statusClass = '';

        // Kontrola expirací
        if (expirationDate) {
            if (expirationDate < today) {
                statusBadge = '<span class="badge bg-danger shadow-sm">Expirováno</span>';
                statusClass = 'row-danger';
            } else if (expirationDate <= new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000)) {
                statusBadge = '<span class="badge bg-warning shadow-sm text-dark">Brzy expiruje</span>';
                statusClass = 'row-warning';
            }
        }

        // Kontrola nízkého stavu
        if (material.aktualni_pocet <= material.minimalni_pocet) {
            statusBadge += ' <span class="badge bg-danger shadow-sm ms-1">Nízký stav</span>';
            if (!statusClass) statusClass = 'row-warning';
        }

        const expirationText = expirationDate ?
            expirationDate.toLocaleDateString('cs-CZ') :
            'Není stanovena';

        const row = document.createElement('tr');
        row.className = statusClass;
        row.innerHTML = `
            <td><span class="fw-bold text-primary">${material.lekarnicky_nazev}</span></td>
            <td>${material.nazev_materialu}</td>
            <td><small class="text-muted text-uppercase">${material.typ_materialu}</small></td>
            <td class="text-center"><span class="badge bg-info text-dark">${material.aktualni_pocet} ${material.jednotka}</span></td>
            <td class="text-center"><small class="text-muted">${material.minimalni_pocet} ${material.jednotka}</small></td>
            <td><i class="fa-regular fa-calendar-alt me-1"></i> ${expirationText}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-end px-4">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-link text-primary p-0 me-3" onclick="editMaterial(${material.id})">
                        <i class="fa-solid fa-pen-to-square fs-5"></i>
                    </button>
                    <button class="btn btn-link text-danger p-0" onclick="deleteMaterial(${material.id})">
                        <i class="fa-solid fa-trash-can fs-5"></i>
                    </button>
                </div>
            </td>
        `;

        return row;
    }

    // Načtení úrazů
    async function loadUrazy() {
        const tbody = document.getElementById('urazy-tbody');
        if (!tbody) return;

        // Zrušit starou instanci DataTables (pokud existuje)
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#urazyTable')) {
            $('#urazyTable').DataTable().destroy();
            $(tbody).empty();
        }

        try {
            const urazy = await apiCall('/api/lekarnicke/urazy');
            appData.urazy = urazy || [];

            if (!urazy || urazy.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Žádné záznamy úrazů</td></tr>';
            } else {
                tbody.innerHTML = urazy.map(u => {
                    const datum = u.datum_cas_urazu
                        ? new Date(u.datum_cas_urazu).toLocaleString('cs-CZ')
                        : '—';
                    const zamestnanec = u.zamestnanec
                        ? `${u.zamestnanec.prijmeni} ${u.zamestnanec.jmeno}`
                        : '—';
                    const lekarnicka = u.lekarnicky
                        ? u.lekarnicky.nazev
                        : '—';
                    const zavaznostBadge = {
                        'lehky': '<span class="badge bg-success shadow-sm">Lehký</span>',
                        'stredni': '<span class="badge bg-warning shadow-sm text-dark">Střední</span>',
                        'tezky': '<span class="badge bg-danger shadow-sm">Těžký</span>'
                    }[u.zavaznost] || u.zavaznost || '—';

                    return `
                        <tr>
                            <td><i class="fa-regular fa-clock me-1 text-muted"></i> ${datum}</td>
                            <td><span class="fw-bold">${zamestnanec}</span></td>
                            <td>${u.misto_urazu || '—'}</td>
                            <td>${zavaznostBadge}</td>
                            <td><span class="text-primary">${lekarnicka}</span></td>
                            <td class="text-end px-4">
                                <button class="btn btn-link text-danger p-0"
                                        onclick="deleteUraz(${u.id})"
                                        title="Smazat záznam">
                                    <i class="fa-solid fa-trash-can fs-5"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // Inicializace DataTable
            if ($.fn.DataTable) {
                const tableConfig = {
                    responsive: true,
                    pageLength: 10,
                    language: { url: '/assets/cs.json' }
                };
                $('#urazyTable').DataTable(tableConfig);
            }

        } catch (error) {
            console.error('Chyba při načítání úrazů:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Chyba při načítání záznamů</td></tr>';
        }
    }

    // Nastavení výkazů
    function setupVykazy() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

        const exportOdEl = document.getElementById('export-od');
        const exportDoEl = document.getElementById('export-do');

        if (exportOdEl) exportOdEl.value = firstDay.toISOString().split('T')[0];
        if (exportDoEl) exportDoEl.value = today.toISOString().split('T')[0];
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
        modal.style.display = 'none';
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');

        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
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

    function resetMaterialForm() {
        const form = document.getElementById('material-form');
        if (form) form.reset();
        document.getElementById('material-id').value = '';
        document.getElementById('materialModalTitle').textContent = 'Přidat materiál';
        document.getElementById('material-submit-btn').textContent = 'Uložit';
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

    // Vykreslí obsah detail modalu a otevře ho
    function openDetailLekarnicky(data) {
        const fmt = (d) => d ? new Date(d).toLocaleDateString('cs-CZ') : '—';

        document.getElementById('detail-lekarnicky-title').textContent = 'Lékárnička: ' + data.nazev;
        document.getElementById('detail-umisteni').textContent = data.umisteni || '—';
        document.getElementById('detail-zodpovedna').textContent = data.zodpovedna_osoba || '—';
        document.getElementById('detail-status').textContent = data.status || '—';
        document.getElementById('detail-posledni').textContent = fmt(data.posledni_kontrola);
        document.getElementById('detail-dalsi').textContent = fmt(data.dalsi_kontrola);
        document.getElementById('detail-popis').textContent = data.popis || '—';

        // Materiál
        const materialTbody = document.getElementById('detail-material-tbody');
        if (data.material && data.material.length > 0) {
            materialTbody.innerHTML = data.material.map(m => `
                <tr>
                    <td>${m.nazev_materialu}</td>
                    <td>${m.typ_materialu}</td>
                    <td>${m.aktualni_pocet} ${m.jednotka}</td>
                    <td>${m.minimalni_pocet} / ${m.maximalni_pocet}</td>
                    <td>${m.datum_expirace ? fmt(m.datum_expirace) : '—'}</td>
                </tr>
            `).join('');
        } else {
            materialTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Žádný materiál</td></tr>';
        }

        // Úrazy
        const urazyTbody = document.getElementById('detail-urazy-tbody');
        if (data.urazy && data.urazy.length > 0) {
            urazyTbody.innerHTML = data.urazy.map(u => {
                const z = u.zamestnanec ? `${u.zamestnanec.prijmeni} ${u.zamestnanec.jmeno}` : '—';
                return `
                    <tr>
                        <td>${fmt(u.datum_cas_urazu)}</td>
                        <td>${z}</td>
                        <td>${u.misto_urazu || '—'}</td>
                        <td>${u.zavaznost || '—'}</td>
                    </tr>
                `;
            }).join('');
        } else {
            urazyTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Žádný úraz</td></tr>';
        }

        // Otevřít modal (Bootstrap)
        const modalEl = document.getElementById('detailLekarnickyModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    // ========== GLOBÁLNÍ FUNKCE ==========

    // Globální funkce pro onclick handlery

    window.viewLekarnicky = async function(id) {
        try {
            const data = await apiCall(`/api/lekarnicke/${id}`);
            openDetailLekarnicky(data);
        } catch (error) {
            console.error('Chyba při načítání detailu:', error);
        }
    };

    window.kontrolaLekarnicky = async function(id) {
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
    };

window.editMaterial = async function(id) {
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
    };

    window.deleteMaterial = async function(id) {
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
            }
        } catch (error) {
            console.error('Chyba při mazání materiálu:', error);
        }
    };
    window.deleteUraz = async function(id) {
        if (!confirm('Opravdu chcete smazat tento záznam úrazu?')) return;

        try {
            const result = await apiCall(`/api/lekarnicke/urazy/${id}`, {
                method: 'DELETE'
            });
            if (result.success) {
                showNotification(result.message || 'Záznam smazán', 'success');
                await loadUrazy();
                await loadDashboard(); // aktualizuj počet úrazů v dashboard statistice
            }
        } catch (error) {
            console.error('Chyba při mazání úrazu:', error);
        }
    };

    // ========== EVENT LISTENERY ==========

    // Event listenery
    function setupEventListeners() {
        // Navigace mezi sekcemi
        document.querySelectorAll('.navigation-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const section = e.currentTarget.getAttribute('data-section');
                showSection(section);
            });
        });
    }

    // Nastavení výkazů a statistik
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

        // Inicializace grafů
        renderCharts();

        // Event listener pro export
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

            // 3. Kontroly (Bar Chart) - Vylepšeno na reálná data (Pending vs Done)
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
                        indexAxis: 'y', // Horizontální graf
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
        // Filter materiálu podle lékárničky
        const materialFilter = document.getElementById('material-lekarnicky-filter');
        if (materialFilter) {
            materialFilter.addEventListener('change', (e) => {
                filterMaterial(e.target.value);
            });
        }

        // Export výkazu
        const exportBtn = document.getElementById('export-vykaz');
        if (exportBtn) {
            exportBtn.addEventListener('click', handleExportVykaz);
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

    // Filter materiálu
    function filterMaterial(lekarnicky_id) {
        const rows = document.querySelectorAll('#material-tbody tr');

        if (!lekarnicky_id) {
            rows.forEach(row => row.style.display = '');
            return;
        }

        const selectedLekarnicky = appData.lekarnicke.find(l => l.id == lekarnicky_id);
        if (!selectedLekarnicky) return;

        // Skrýt všechny řádky
        rows.forEach(row => row.style.display = 'none');

        // Zobrazit pouze řádky s materiály z vybrané lékárničky
        selectedLekarnicky.material?.forEach(material => {
            // Najít řádek obsahující název materiálu
            rows.forEach(row => {
                if (row.textContent.includes(material.nazev_materialu)) {
                    row.style.display = '';
                }
            });
        });
    }

    // Handler pro export výkazu
    async function handleExportVykaz() {
        const odEl = document.getElementById('export-od');
        const doEl = document.getElementById('export-do');

        if (!odEl || !doEl) {
            showNotification('Chyba: Formulář není k dispozici', 'error');
            return;
        }

        const od = odEl.value;
        const do_date = doEl.value;

        try {
            const result = await apiCall(`/api/lekarnicke/export-vykaz?od=${od}&do=${do_date}`);
            console.log('Export data:', result);
            showNotification('Výkaz byl vygenerován', 'success');
        } catch (error) {
            console.error('Chyba při exportu výkazu:', error);
        }
    }

    // ========== OPRAVENÝ HANDLER PRO PŘIDÁNÍ LÉKÁRNIČKY ==========

    // Handler pro přidání lékárničky - OPRAVENO
    async function handleAddLekarnicky(e) {
        e.preventDefault();
        console.log('Odesílám formulář lékárničky...');

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        console.log('Data k odeslání:', data);

        try {
            const result = await apiCall('/api/lekarnicke', {
                method: 'POST',
                body: JSON.stringify(data)
            });

            console.log('Výsledek API:', result);

            if (result.success) {
                // JEDNODUCHÉ a SPOLEHLIVÉ zavření modalu
                const modal = document.getElementById('addLekarnickModal');
                if (modal) {
                    // Skrýt modal
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    modal.setAttribute('aria-hidden', 'true');
                    modal.removeAttribute('aria-modal');

                    // Odebrat backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }

                    // Obnovit body stav
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';

                    console.log('Modal zavřen úspěšně');
                }

                // Reset formuláře
                e.target.reset();
                console.log('Formulář resetován');

                // Zobrazit úspěšnou zprávu
                showNotification(result.message || 'Lékárnička byla úspěšně přidána', 'success');

                // Znovu načíst dashboard
                console.log('Načítám dashboard...');
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

    // Inicializace
    setupEventListeners();
    loadDashboard();
}

