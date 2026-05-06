function administraceAll() {

    const addUserForm = document.getElementById('add-admin-form');
    const userList = document.getElementById('user-list');
    const zpravaPri = document.getElementById('zprava-pri');
    const zpravaErr = document.getElementById('zprava-err');
    const addButton = document.getElementById('add-user-form');
    const formZebra = document.getElementById('add-user-form-zobrazit');
    const roleSelect = document.getElementById('role');

    if (zpravaPri) zpravaPri.textContent = '';
    if (zpravaErr) zpravaErr.textContent = '';

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token nebyl nalezen!');
        alert('Chyba: Chybí bezpečnostní token');
        return;
    }

    // Informace o aktuálním přihlášeném uživateli
    const currentUserIdMeta = document.querySelector('meta[name="current-user-id"]');
    const currentUserId = currentUserIdMeta ? parseInt(currentUserIdMeta.content, 10) || null : null;

    const currentUserSuperAdminMeta = document.querySelector('meta[name="current-user-is-super-admin"]');
    const currentUserIsSuperAdmin = currentUserSuperAdminMeta
        ? currentUserSuperAdminMeta.content === 'true'
        : false;

    function displayMessage(element, message, isSuccess = true) {
        zpravaPri.textContent = '';
        zpravaErr.textContent = '';
        zpravaPri.classList.add('d-none');
        zpravaErr.classList.add('d-none');

        element.classList.remove('alert-success', 'alert-danger', 'shadow-success', 'shadow-error');
        element.textContent = message;

        if (isSuccess) {
            element.classList.add('alert-success', 'shadow-success');
        } else {
            element.classList.add('alert-danger', 'shadow-error');
        }
        element.classList.remove('d-none');

        setTimeout(() => {
            element.textContent = '';
            element.classList.add('d-none');
            element.classList.remove('alert-success', 'alert-danger', 'shadow-sm', 'shadow-error');
        }, 10000);
    }

    // ========== NAČTENÍ DOSTUPNÝCH RBAC ROLÍ DO SELECTU ==========
    function loadAvailableRoles() {
        if (!roleSelect) return;

        fetch('/api/users/available-roles', {
            headers: { 'Accept': 'application/json' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Chyba při načítání rolí.');
                return response.json();
            })
            .then(roles => {
                roleSelect.innerHTML = '<option value="" selected disabled>Vyberte uživatelskou roli</option>';
                roles.forEach(role => {
                    const option = document.createElement('option');
                    option.value = role.name;
                    option.textContent = role.display_name;
                    if (role.description) {
                        option.title = role.description;
                    }
                    roleSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Chyba při načítání rolí:', error);
                roleSelect.innerHTML = '<option value="" disabled>Nepodařilo se načíst role</option>';
            });
    }

    loadAvailableRoles();

    // ========== PŘIDÁNÍ UŽIVATELE ==========
    if (addUserForm) {
        addUserForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(addUserForm);
            const data = Object.fromEntries(formData.entries());

            fetch('/add_users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(response => {
                    return response.json().then(data => {
                        if (!response.ok) {
                            if (data.errors) {
                                const messages = Object.values(data.errors).flat().join(' ');
                                throw new Error(messages);
                            }
                            throw new Error(data.message || 'Neznámá chyba serveru při přidávání uživatele.');
                        }
                        return data;
                    });
                })
                .then(data => {
                    if (data.status === 'success') {
                        displayMessage(zpravaPri, data.message || 'Uživatel byl vytvořen.', true);
                        addUserForm.reset();
                        loadAvailableRoles();
                        nactiUsers();
                    } else {
                        displayMessage(zpravaErr, 'Je nám líto, ale někde se stala chyba: ' + (data.message || 'Neznámá chyba.'), false);
                    }
                })
                .catch(error => {
                    console.error('Chyba při přidávání uživatele:', error);
                    displayMessage(zpravaErr, 'Chyba při přidávání uživatele: ' + (error.message || 'Nelze se připojit k serveru.'), false);
                });
        });
    }

    // ========== HELPER: Vyhodnocení, zda lze daného uživatele smazat ==========
    function canDeleteUser(user) {
        // 1. Nelze smazat sám sebe
        if (currentUserId !== null && user.id === currentUserId) {
            return { allowed: false, reason: 'Nelze smazat svůj vlastní účet' };
        }

        // 2. Pouze super_admin může smazat jiného super_admina
        const targetIsSuperAdmin = user.role_names && user.role_names.includes('super_admin');
        if (targetIsSuperAdmin && !currentUserIsSuperAdmin) {
            return { allowed: false, reason: 'Pouze super_admin může smazat jiného super_admina' };
        }

        return { allowed: true, reason: '' };
    }

    // ========== NAČTENÍ SEZNAMU UŽIVATELŮ ==========
    function nactiUsers() {
        fetch('/adminUser', {})
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.message || 'Chyba při načítání uživatelů.');
                    }
                    return data;
                });
            })
            .then(users => {
                if (!userList) return;

                userList.innerHTML = '';

                if (Array.isArray(users) && users.length > 0) {
                    users.forEach(user => {
                        const rolesHtml = (user.roles && user.roles.length > 0)
                            ? user.roles.map(r => `<span class="badge bg-primary me-1">${escapeHtml(r)}</span>`).join('')
                            : '<span class="text-muted">Bez role</span>';

                        // Zhodnocení, zda lze uživatele smazat
                        const deleteCheck = canDeleteUser(user);
                        const deleteButtonHtml = deleteCheck.allowed
                            ? `<button class="delete-user" data-id="${user.id}" title="Odebrat uživatele">Odebrat</button>`
                            : `<button class="delete-user" disabled title="${escapeHtml(deleteCheck.reason)}" style="opacity: 0.5; cursor: not-allowed;">Odebrat</button>`;

                        // Označení vlastního řádku jemnou indikací
                        const isCurrentUser = currentUserId !== null && user.id === currentUserId;
                        const youBadge = isCurrentUser ? ' <small class="text-success">(vy)</small>' : '';

                        const tr = document.createElement('tr');
                        tr.setAttribute('data-user-id', user.id);
                        if (isCurrentUser) tr.style.backgroundColor = 'rgba(76, 175, 80, 0.08)';

                        tr.innerHTML = `
                            <td>${escapeHtml(user.username)}${youBadge}</td>
                            <td>${escapeHtml(user.firstname)}</td>
                            <td>${escapeHtml(user.lastname)}</td>
                            <td>${escapeHtml(user.email)}</td>
                            <td>${rolesHtml}</td>
                            <td>
                                ${deleteButtonHtml}
                                <button class="send-mail"
                                        data-username="${escapeHtml(user.username)}"
                                        data-email="${escapeHtml(user.email)}"
                                        data-firstname="${escapeHtml(user.firstname)}"
                                        data-lastname="${escapeHtml(user.lastname)}">
                                    Odešli přihlašovací údaje
                                </button>
                            </td>
                        `;
                        userList.appendChild(tr);
                    });

                    // Smazání uživatele (pouze pro non-disabled tlačítka)
                    document.querySelectorAll('.delete-user:not([disabled])').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const id = e.target.getAttribute('data-id');
                            if (!confirm('Opravdu chcete odebrat tohoto uživatele?')) return;

                            fetch(`/users/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                    'Accept': 'application/json'
                                }
                            })
                                .then(response => response.json().then(result => {
                                    if (!response.ok) {
                                        throw new Error(result.message || 'Chyba při odstraňování uživatele.');
                                    }
                                    return result;
                                }))
                                .then(result => {
                                    if (result.status === 'success') {
                                        displayMessage(zpravaPri, result.message || 'Uživatel byl odebrán.', true);
                                        nactiUsers();
                                    } else {
                                        displayMessage(zpravaErr, 'Chyba při odebrání: ' + (result.message || 'Neznámá chyba.'), false);
                                    }
                                })
                                .catch(error => {
                                    console.error('Chyba při odstraňování uživatele:', error);
                                    displayMessage(zpravaErr, 'Chyba: ' + (error.message || 'Nelze se připojit k serveru.'), false);
                                });
                        });
                    });

                    // Odeslání přihlašovacích údajů
                    document.querySelectorAll('.send-mail').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const username = e.target.getAttribute('data-username');
                            const email = e.target.getAttribute('data-email');
                            const firstName = e.target.getAttribute('data-firstname');
                            const lastName = e.target.getAttribute('data-lastname');

                            fetch('/send-login-email', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                },
                                body: `username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&firstname=${encodeURIComponent(firstName)}&lastname=${encodeURIComponent(lastName)}`
                            })
                                .then(response => response.json().then(result => {
                                    if (!response.ok) {
                                        throw new Error(result.message || 'Chyba při odesílání přihlašovacích údajů.');
                                    }
                                    return result;
                                }))
                                .then(result => {
                                    if (result.status === 'success') {
                                        // Generická odpověď ze serveru - server neříká, jestli uživatel skutečně existoval
                                        displayMessage(zpravaPri, result.message || 'Požadavek na reset přihlašovacích údajů byl zpracován.', true);
                                    } else {
                                        displayMessage(zpravaErr, 'Chyba: ' + (result.message || 'Neznámá chyba.'), false);
                                    }
                                })
                                .catch(error => {
                                    console.error('Chyba při odesílání přihlašovacích údajů:', error);
                                    displayMessage(zpravaErr, 'Chyba: ' + (error.message || 'Nelze se připojit k serveru.'), false);
                                });
                        });
                    });
                } else {
                    userList.innerHTML = '<tr><td colspan="6" class="text-center">Žádní uživatelé k zobrazení.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Chyba při načítání uživatelů:', error);
                if (userList) {
                    userList.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Chyba při načítání uživatelů: ${error.message || 'Nelze se připojit k serveru.'}</td></tr>`;
                }
            });
    }

    // Helper proti XSS
    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    nactiUsers();

    // Přepínání zobrazení formuláře
    if (addButton && formZebra) {
        addButton.addEventListener('click', function () {
            if (formZebra.style.display === 'none') {
                formZebra.style.display = 'block';
                addButton.textContent = 'Zavřít formulář';
            } else {
                formZebra.style.display = 'none';
                addButton.innerHTML = '<i id="add-icon" class="fa-solid fa-plus fa-4x text-success"></i>Přidat uživatelský účet';
            }
        });
    }
}

export { administraceAll };
