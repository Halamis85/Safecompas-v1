
export function permissions() {
    console.log('Permissions modul inicializován');

    // Globální data
    let appData = {
        roles: [],
        permissions: {},
        users: [],
        lekarnicke: [],
        currentSection: 'roles',
        editingRole: null
    };

    // CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // API helper
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
                throw new Error(data.message || data.error || 'API chyba');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            showNotification('Chyba při komunikaci se serverem: ' + error.message, 'error');
            throw error;
        }
    }

    // Načtení dat z API
    async function loadData() {
        const loading = document.getElementById('loading');
        loading.style.display = 'block';

        try {
            const data = await apiCall('/api/permissions/dashboard');

            appData.roles = data.roles || [];
            appData.permissions = data.permissions || {};
            appData.users = data.users || [];
            appData.lekarnicke = data.lekarnicke || [];

            showDashboard();
            loadCurrentSection();

        } catch (error) {
            console.error('Chyba při načítání dat:', error);
            showNotification('Chyba při načítání dat oprávnění', 'error');
        } finally {
            loading.style.display = 'none';
        }
    }

    // Zobrazení dashboardu
    function showDashboard() {
        const navigation = document.getElementById('navigation-buttons');
        if (navigation) {
            navigation.style.display = 'block';
        }
    }

    // Zobrazení sekce
    function showSection(section) {
        appData.currentSection = section;

        // Skrýt všechny sekce
        document.querySelectorAll('.content-section').forEach(el => {
            el.style.display = 'none';
        });

        // Zobrazit vybranou sekci
        const targetSection = document.getElementById(`section-${section}`);
        if (targetSection) {
            targetSection.style.display = 'block';
        }

        // Aktualizovat navigaci
        document.querySelectorAll('.section-btn').forEach(btn => {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        });

        const activeBtn = document.querySelector(`[data-section="${section}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('btn-outline-primary');
            activeBtn.classList.add('btn-primary', 'active');
        }

        // Načíst obsah sekce
        loadCurrentSection();
    }

    // Načtení obsahu aktuální sekce
    function loadCurrentSection() {
        switch (appData.currentSection) {
            case 'roles':
                renderRoles();
                renderPermissionsCheckboxes();
                break;
            case 'users':
                renderUsers();
                break;
            case 'permissions':
                renderPermissionsOverview();
                break;
        }
    }

    // ========== ROLE SEKCE ==========

    // Vykreslení seznamu rolí
    function renderRoles() {
        const container = document.getElementById('roles-list');
        if (!container) return;

        container.innerHTML = '';

        if (appData.roles.length === 0) {
            container.innerHTML = '<div class="list-group-item">Žádné role nenalezeny</div>';
            return;
        }

        appData.roles.forEach(role => {
            const roleItem = createRoleItem(role);
            container.appendChild(roleItem);
        });
    }

    // Vytvoření položky role
    function createRoleItem(role) {
        const div = document.createElement('div');
        div.className = 'list-group-item';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">${role.display_name}</h6>
                    <p class="mb-1 text-muted">${role.description || 'Bez popisu'}</p>
                    <small>Oprávnění: ${role.permissions ? role.permissions.length : 0}</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-primary" onclick="editRole(${role.id})">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                </div>
            </div>
        `;

        return div;
    }

    // Vykreslení checkboxů pro oprávnění
    function renderPermissionsCheckboxes() {
        const container = document.getElementById('permissions-checkboxes');
        if (!container) return;

        container.innerHTML = '';

        Object.keys(appData.permissions).forEach(module => {
            const moduleDiv = document.createElement('div');
            moduleDiv.className = 'mb-3';

            const moduleTitle = document.createElement('h6');
            moduleTitle.textContent = module.toUpperCase();
            moduleDiv.appendChild(moduleTitle);

            appData.permissions[module].forEach(perm => {
                const checkDiv = document.createElement('div');
                checkDiv.className = 'form-check';
                checkDiv.innerHTML = `
                    <input
                        type="checkbox"
                        class="form-check-input permission-checkbox"
                        value="${perm.id}"
                        id="perm-${perm.id}">
                    <label class="form-check-label" for="perm-${perm.id}">
                        ${perm.display_name}
                    </label>
                `;
                moduleDiv.appendChild(checkDiv);
            });

            container.appendChild(moduleDiv);
        });
    }

    // Editace role
    window.editRole = function(roleId) {
        const role = appData.roles.find(r => r.id === roleId);
        if (!role) return;

        appData.editingRole = role;

        // Vyplnění formuláře
        const form = document.getElementById('role-form');
        if (form) {
            form.name.value = role.name;
            form.display_name.value = role.display_name;
            form.description.value = role.description || '';
        }

        // Označení checkboxů
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        if (role.permissions) {
            role.permissions.forEach(perm => {
                const checkbox = document.getElementById(`perm-${perm.id}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }

        // Aktualizace titulku
        const title = document.getElementById('role-form-title');
        if (title) {
            title.textContent = 'Upravit roli';
        }
    };

    // Reset formuláře role
    function resetRoleForm() {
        appData.editingRole = null;

        const form = document.getElementById('role-form');
        if (form) {
            form.reset();
        }

        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        const title = document.getElementById('role-form-title');
        if (title) {
            title.textContent = 'Nová role';
        }
    }

    // Uložení role
    async function saveRole(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        // Získání vybraných oprávnění
        const selectedPermissions = [];
        document.querySelectorAll('.permission-checkbox:checked').forEach(checkbox => {
            selectedPermissions.push(parseInt(checkbox.value));
        });
        data.permission_ids = selectedPermissions;

        try {
            let result;

            if (appData.editingRole) {
                // Aktualizace role
                result = await apiCall(`/api/permissions/roles/${appData.editingRole.id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });

                // Přiřazení oprávnění
                if (result.success) {
                    await apiCall(`/api/permissions/roles/${appData.editingRole.id}/permissions`, {
                        method: 'POST',
                        body: JSON.stringify({ permission_ids: selectedPermissions })
                    });
                }
            } else {
                // Vytvoření nové role
                result = await apiCall('/api/permissions/roles', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                // Přiřazení oprávnění k nové roli
                if (result.success && result.role) {
                    await apiCall(`/api/permissions/roles/${result.role.id}/permissions`, {
                        method: 'POST',
                        body: JSON.stringify({ permission_ids: selectedPermissions })
                    });
                }
            }

            if (result.success) {
                showNotification(result.message, 'success');
                resetRoleForm();
                await loadData(); // Obnovit data
            }
        } catch (error) {
            console.error('Chyba při ukládání role:', error);
        }
    }

    // ========== UŽIVATELÉ SEKCE ==========

    // Vykreslení tabulky uživatelů
    function renderUsers() {
        const tbody = document.getElementById('users-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (appData.users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Žádní uživatelé nenalezeni</td></tr>';
            return;
        }

        appData.users.forEach(user => {
            const row = createUserRow(user);
            tbody.appendChild(row);
        });
    }

    // Vytvoření řádku uživatele
    function createUserRow(user) {
        const tr = document.createElement('tr');

        const rolesHtml = user.roles ?
            user.roles.map(role => `<span class="badge bg-primary me-1">${role.display_name}</span>`).join('') :
            '<span class="text-muted">Žádné role</span>';

        tr.innerHTML = `
            <td>${user.firstname} ${user.lastname} (${user.username})</td>
            <td>${rolesHtml}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editUserRoles(${user.id})">
                    Upravit role
                </button>
            </td>
        `;

        return tr;
    }

    // Editace uživatelských rolí
    window.editUserRoles = function(userId) {
        const user = appData.users.find(u => u.id === userId);
        if (!user) return;

        // TODO: Implementovat modal pro editaci rolí
        showNotification(`Editace rolí pro uživatele ${user.username} - zatím neimplementováno`, 'info');
    };

    // ========== OPRÁVNĚNÍ SEKCE ==========

    // Vykreslení přehledu oprávnění
    function renderPermissionsOverview() {
        const container = document.getElementById('permissions-overview');
        if (!container) return;

        container.innerHTML = '';

        Object.keys(appData.permissions).forEach(module => {
            const moduleCard = document.createElement('div');
            moduleCard.className = 'card mb-3';

            moduleCard.innerHTML = `
                <div class="card-header">
                    <h5 class="mb-0">${module.toUpperCase()}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        ${appData.permissions[module].map(perm => `
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-shield-halved text-primary me-2"></i>
                                    <div>
                                        <strong>${perm.display_name}</strong><br>
                                        <small class="text-muted">${perm.name}</small>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            container.appendChild(moduleCard);
        });
    }

    // ========== NOTIFIKACE ==========

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

    // ========== EVENT LISTENERY ==========

    function setupEventListeners() {
        // Navigace mezi sekcemi
        document.querySelectorAll('.section-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const section = e.target.getAttribute('data-section');
                showSection(section);
            });
        });

        // Formulář role
        const roleForm = document.getElementById('role-form');
        if (roleForm) {
            roleForm.addEventListener('submit', saveRole);
        }

        // Zrušení editace role
        const cancelBtn = document.getElementById('cancel-role');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', resetRoleForm);
        }
    }

    // ========== INICIALIZACE ==========

    // Inicializace
    setupEventListeners();
    loadData();
}
