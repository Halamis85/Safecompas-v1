function administraceAll() {

    const addUserForm = document.getElementById('add-admin-form');
    const userList = document.getElementById('user-list');
    const zpravaPri = document.getElementById('zprava-pri');
    const zpravaErr = document.getElementById('zprava-err');
    const addButton =document.getElementById('add-user-form');
    const formZebra = document.getElementById('add-user-form-zobrazit')

    if (zpravaPri) {
        zpravaPri.textContent = '';
    }
    if (zpravaErr) {
        zpravaErr.textContent = '';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token nebyl nalezen!');
        alert('Chyba: Chybí bezpečnostní token');
        return;
    }

    function displayMessage(element, message, isSuccess = true) {
        zpravaPri.textContent = '';
        zpravaErr.textContent = '';

        // Skryjeme oba elementy (pro jistotu, aby se neobjevily zároveň)
        zpravaPri.classList.add('d-none');
        zpravaErr.classList.add('d-none');

        // Odstraníme stylingové třídy, které by mohly zůstat
        element.classList.remove('alert-success', 'alert-danger', 'shadow-success', 'shadow-error');

        // Nastavíme text zprávy
        element.textContent = message;

        if (isSuccess) {
            element.classList.add('alert-success');
            element.classList.add('shadow-success'); // Předpokládám, že je to 'shadow-error' pro obě varianty, nebo shadow-success
        } else {
            element.classList.add('alert-danger');
            element.classList.add('shadow-error');
        }
        // Zobrazíme element odstraněním d-none
        element.classList.remove('d-none');

        // Nastavíme automatické skrytí
        setTimeout(() => {
            element.textContent = ''; // Vyčistíme text
            element.classList.add('d-none'); // Skryjeme element
            element.classList.remove('alert-success', 'alert-danger', 'shadow-sm', 'shadow-error');
        }, 10000);
    }

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
                            throw new Error(data.message || 'Neznámá chyba serveru při přidávání uživatele.');
                        }
                        return data;
                    });
                })
                .then(data => {
                    if (data.status === 'success') {
                        displayMessage(zpravaPri,(data.message ||
                            'Nelze se připojit k databázi nebo serveru'),true);
                        addUserForm.reset();
                        nactiUsers();
                    } else {
                        displayMessage(zpravaErr,'Je nám líto ale někde se stala chyba:' +
                            ( data.message || 'Neznámá chyba.'), false);
                    }
                })
                .catch(error => {
                    console.error('Chyba při přidávání uživatele.' , error);
                    displayMessage(zpravaErr,'Chyba při přidávání uživatele.' + (error.message ||
                        'Nelze se připojit k databázi nebo serveru '),false);
                });
        });
    }
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
                if (userList) {
                    userList.innerHTML = '';
                    // Kontrola, zda 'users' je pole a není prázdné
                    if (Array.isArray(users) && users.length > 0) {
                        users.forEach(user => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                            <td>${user.username}</td>
                            <td>${user.firstname}</td>
                            <td>${user.lastname}</td>
                            <td>${user.email}</td>
                            <td>${user.role}</td>
                            <td>
                             <button class="delete-user" data-id="${user.id}">Odebrat</button>
                             <button class="send-mail"
                             data-username="${user.username}"
                              data-email="${user.email}"
                              data-firstname="${user.firstname}"
                             data-lastname="${user.lastname}">
                             Odešli přihlašovací udaje</button>
                            </td>
                        `;
                            userList.appendChild(tr);
                        });

                        document.querySelectorAll('.delete-user').forEach(button => {
                            button.addEventListener('click', (e) => {
                                const id = e.target.getAttribute('data-id');
                                fetch(`/users/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                        'Accept': 'application/json'
                                    }
                                })
                                    .then(response => {
                                        return response.json().then(result => {
                                            if (!response.ok) {
                                                throw new Error(result.message || 'Chyba při odstraňování uživatele.');
                                            }
                                            return result;
                                        });
                                    })
                                    .then(result => {
                                        if (result.status === 'success') {
                                            displayMessage(zpravaPri, 'Uživatel byl odebrán.', true);
                                            nactiUsers();
                                        } else {
                                            displayMessage(zpravaErr, 'Chyba při odebrání uživatele: ' + (result.message || 'Neznámá chyba.'), false);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Chyba při odstraňování uživatele:', error);
                                        displayMessage(zpravaErr, 'Chyba při odstraňování uživatele: ' + (error.message || 'Nelze se připojit k serveru.'), false);
                                    });
                            });
                        });

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
                                    .then(response => {
                                        return response.json().then(result => {
                                            if (!response.ok) {
                                                throw new Error(result.message || 'Chyba při odesílání přihlašovacích údajů.');
                                            }
                                            return result;
                                        });
                                    })
                                    .then(result => {
                                        if (result.status === 'success') {
                                            displayMessage(zpravaPri, 'Přihlašovací údaje byly odeslány na e-mail ' + email, true);
                                        } else {
                                            displayMessage(zpravaErr, 'Chyba při odesílání přihlašovacích údajů: ' + (result.message || 'Neznámá chyba.'), false);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Chyba při odesílání přihlašovacích údajů:', error);
                                        displayMessage(zpravaErr, 'Chyba při odesílání přihlašovacích údajů: ' + (error.message || 'Nelze se připojit k serveru.'), false);
                                    });
                            });
                        });
                    } else {
                        // Pokud nejsou žádní uživatelé, zobrazte zprávu
                        userList.innerHTML = '<tr><td colspan="6" class="text-center">Žádní uživatelé k zobrazení.</td></tr>';
                    }
                }
            })
            .catch(error => {
                console.error("Chyba při načítání uživatelů:", error);
                // Zde můžete také zobrazit chybovou zprávu uživateli, pokud se nepodaří načíst seznam
                if (userList) {
                    userList.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Chyba při načítání uživatelů: ${error.message || 'Nelze se připojit k serveru.'}</td></tr>`;
                }
            });
    }
    nactiUsers();

    if (addButton && formZebra) {
        addButton.addEventListener('click', function () {
            if (formZebra.style.display === 'none') {
                formZebra.style.display = 'block';
                addButton.textContent = 'Zavřít formulář';
            } else {
                formZebra.style.display = 'none';
                addButton.innerHTML = '<i id="add-icon" class="fa-solid fa-plus fa-4x text-success"></i>Přidat uživatelský účet ';
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', administraceAll);
export {administraceAll};
