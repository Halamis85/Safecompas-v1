// resources/js/plan-budovy.js
//
// Modul pro plán budovy s drag&drop puntíky.
// Importuje se a volá z lekarnicky.js.

export function initPlanBudovy({ apiCall, showNotification, getLekarnicky, openDetailLekarnicky }) {

    let editing = false;
    let dragState = null; // { id, startX, startY, marker, fromUnassigned }

    const modalEl = document.getElementById('planBudovyModal');
    if (!modalEl) return;

    const container       = document.getElementById('plan-container');
    const image           = document.getElementById('plan-image');
    const markersLayer    = document.getElementById('plan-markers');
    const editToggle      = document.getElementById('planEditToggle');
    const helpText        = document.getElementById('plan-help-text');
    const unassignedWrap  = document.getElementById('plan-unassigned-wrapper');
    const unassignedList  = document.getElementById('plan-unassigned-list');

    if (!container || !image || !markersLayer) return;

    // ============================================================
    // RENDER
    // ============================================================

    function render() {
        const lekarnicky = getLekarnicky() || [];

        markersLayer.innerHTML = '';
        unassignedList.innerHTML = '';

        let unassignedCount = 0;

        lekarnicky.forEach(l => {
            const hasPosition = l.plan_x !== null && l.plan_x !== undefined
                             && l.plan_y !== null && l.plan_y !== undefined;

            if (hasPosition) {
                renderMarker(l);
            } else {
                renderUnassigned(l);
                unassignedCount++;
            }
        });

        // Zobrazit/skrýt sekci "bez pozice" - jen v editing režimu
        if (editing && unassignedCount > 0) {
            unassignedWrap.classList.remove('d-none');
        } else {
            unassignedWrap.classList.add('d-none');
        }
    }

    function renderMarker(lekarnicky) {
        const marker = document.createElement('div');
        marker.className = `plan-marker status-${lekarnicky.status || 'aktivni'}`;
        marker.style.left = `${lekarnicky.plan_x}%`;
        marker.style.top  = `${lekarnicky.plan_y}%`;
        marker.dataset.id = lekarnicky.id;

        marker.innerHTML = `
            <i class="fa-solid fa-kit-medical"></i>
            <div class="plan-marker-tooltip">
                ${escapeHtml(lekarnicky.nazev)}
                ${lekarnicky.umisteni ? '<br><small>' + escapeHtml(lekarnicky.umisteni) + '</small>' : ''}
            </div>
        `;

        // Klik (jen mimo editing) = otevři detail
        marker.addEventListener('click', (e) => {
            if (editing) return;
            e.stopPropagation();
            if (typeof window.viewLekarnicky === 'function') {
                // Sklouzneme modal plánu - jinak by se otevřel detail nad ním
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                window.viewLekarnicky(lekarnicky.id);
            }
        });

        // Drag start (jen v editing)
        marker.addEventListener('pointerdown', (e) => {
            if (!editing) return;
            startDrag(e, lekarnicky.id, marker, false);
        });

        markersLayer.appendChild(marker);
    }

    function renderUnassigned(lekarnicky) {
        const wrap = document.createElement('div');
        wrap.className = 'plan-unassigned-item';

        const dot = document.createElement('div');
        dot.className = 'plan-unassigned-marker';
        dot.dataset.id = lekarnicky.id;
        dot.innerHTML = '<i class="fa-solid fa-kit-medical"></i>';

        dot.addEventListener('pointerdown', (e) => {
            if (!editing) return;
            startDragFromUnassigned(e, lekarnicky.id, dot);
        });

        const label = document.createElement('span');
        label.className = 'plan-unassigned-label';
        label.textContent = lekarnicky.nazev;

        wrap.appendChild(dot);
        wrap.appendChild(label);
        unassignedList.appendChild(wrap);
    }

    // ============================================================
    // DRAG & DROP
    // ============================================================

    function startDrag(e, id, marker, fromUnassigned) {
        e.preventDefault();
        marker.classList.add('dragging');

        dragState = {
            id,
            marker,
            fromUnassigned,
        };

        // Pro existující puntík: pohybujeme s ním rovnou
        // Pro unassigned: vytvoříme dočasný puntík při prvním pohybu
        if (fromUnassigned) {
            // Vytvořit dočasný puntík
            const ghost = document.createElement('div');
            ghost.className = 'plan-marker status-aktivni dragging';
            ghost.style.pointerEvents = 'none';
            ghost.innerHTML = '<i class="fa-solid fa-kit-medical"></i>';
            markersLayer.appendChild(ghost);
            dragState.ghost = ghost;
        }

        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup',   onPointerUp);
    }

    function startDragFromUnassigned(e, id, dot) {
        startDrag(e, id, dot, true);
    }

    function onPointerMove(e) {
        if (!dragState) return;

        const rect = container.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top)  / rect.height) * 100;

        // Clamp 0-100, aby puntík nevyletěl mimo plán
        const clampedX = Math.max(0, Math.min(100, x));
        const clampedY = Math.max(0, Math.min(100, y));

        const visualMarker = dragState.ghost || dragState.marker;
        if (visualMarker.classList.contains('plan-marker')) {
            visualMarker.style.left = `${clampedX}%`;
            visualMarker.style.top  = `${clampedY}%`;
        }

        dragState.lastX = clampedX;
        dragState.lastY = clampedY;
        dragState.movedInside = (e.clientX >= rect.left && e.clientX <= rect.right
                              && e.clientY >= rect.top  && e.clientY <= rect.bottom);
    }

    async function onPointerUp(e) {
        if (!dragState) return;

        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup',   onPointerUp);

        const { id, marker, ghost, lastX, lastY, movedInside, fromUnassigned } = dragState;

        // Vyčistit dragState ihned, aby další klik na detail fungoval
        dragState = null;

        // Pokud uživatel jen klikl/přidržel bez pohybu na existujícím puntíku - nic neukládat
        if (lastX === undefined || lastY === undefined) {
            if (marker) marker.classList.remove('dragging');
            if (ghost) ghost.remove();
            return;
        }

        // Pokud kurzor skončil mimo plán - zrušit (a u existujícího puntíku ho vrátit)
        if (!movedInside) {
            if (ghost) ghost.remove();
            // Při existujícím puntíku se nemusí nic dělat - render ho přerenderuje
            await persistPosition(id, null);
            return;
        }

        // Uložit novou pozici
        try {
            await persistPosition(id, { x: lastX, y: lastY });
        } catch (e) {
            // chyba se zaloguje v persistPosition; znovu rendrnout, ať puntík skočí na původní pozici
        }

        if (marker) marker.classList.remove('dragging');
        if (ghost) ghost.remove();
    }

    async function persistPosition(id, pos) {
        try {
            const body = pos
                ? { plan_x: pos.x, plan_y: pos.y }
                : { plan_x: null, plan_y: null };

            await apiCall(`/api/lekarnicke/${id}/plan-position`, {
                method: 'POST',
                body: JSON.stringify(body),
            });

            // Aktualizovat lokální appData
            const lekarnicky = getLekarnicky();
            const target = lekarnicky.find(l => l.id === id);
            if (target) {
                target.plan_x = pos ? pos.x : null;
                target.plan_y = pos ? pos.y : null;
            }

            render();

        } catch (error) {
            console.error('Chyba při ukládání pozice:', error);
            showNotification('Pozici se nepodařilo uložit', 'error');
            render(); // přerenderovat, ať skočí zpět na původní pozici
            throw error;
        }
    }

    // ============================================================
    // EDITING TOGGLE
    // ============================================================

    if (editToggle) {
        editToggle.addEventListener('change', (e) => {
            editing = e.target.checked;
            container.classList.toggle('editing', editing);

            if (helpText) {
                helpText.classList.toggle('d-none', !editing);
            }

            render();
        });
    }

    // ============================================================
    // OTEVŘENÍ MODÁLU - rerender (data mohly přibýt)
    // ============================================================

    modalEl.addEventListener('show.bs.modal', () => {
        render();
    });

    // Vrátit api pro vnější volání (např. po loadDashboard)
    return {
        rerender: render,
    };
}

// Helper
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = String(str ?? '');
    return div.innerHTML;
}
