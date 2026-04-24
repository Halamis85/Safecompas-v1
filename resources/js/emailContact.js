// resources/js/emailContact.js
import DataTable from 'datatables.net-dt';

let contactsDataTable = null;

export function emailContact() {
    console.log('emailContact.js: Inicializováno');

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const contactsTable = document.getElementById('contacts-table');
    const contactsList = document.getElementById('contacts-list');
    const formWrapper = document.getElementById('contact-form-wrapper');
    const form = document.getElementById('contact-form');
    const addBtn = document.getElementById('add-contact-btn');
    const cancelBtn = document.getElementById('cancel-contact-btn');
    const modalTitle = document.getElementById('contact-modal-title');
    const messageEl = document.getElementById('contacts-message');

    if (!contactsTable || !form) {
        console.warn('emailContact.js: elementy nebyly nalezeny');
        return;
    }

    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    // Mapa typů z DB na české labely (musí odpovídat Contact::TYPE_LABELS)
    const typeLabels = {
        'supplier': 'Dodavatel',
        'customer': 'Zákazník',
        'user':     'Uživatel',
    };

    // ============= HELPERY =============

    function showMessage(text, isSuccess = true) {
        messageEl.textContent = text;
        messageEl.classList.remove('d-none', 'alert-success', 'alert-danger');
        messageEl.classList.add(isSuccess ? 'alert-success' : 'alert-danger');
        setTimeout(() => messageEl.classList.add('d-none'), 4000);
    }

    function resetForm() {
        form.reset();
        document.getElementById('contact-id').value = '';
        document.getElementById('contact-active').checked = true;
        modalTitle.textContent = 'Přidat emailový kontakt';
    }

    function openForm() {
        resetForm();
        formWrapper.classList.remove('d-none');
    }

    function closeForm() {
        formWrapper.classList.add('d-none');
        resetForm();
    }

    function fillFormForEdit(contact) {
        document.getElementById('contact-id').value = contact.id;
        document.getElementById('contact-name').value = contact.name || '';
        document.getElementById('contact-email').value = contact.email || '';
        document.getElementById('contact-type').value = contact.type || 'supplier';
        document.getElementById('contact-active').checked = !!contact.is_active;
        modalTitle.textContent = 'Upravit kontakt';
        formWrapper.classList.remove('d-none');
    }

    // ============= API =============

    async function apiCall(url, options = {}) {
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            }
        };
        const response = await fetch(url, { ...defaults, ...options });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.message || data.error || 'API chyba');
        }
        return data;
    }

    // ============= LOAD =============

    async function loadContacts() {
        try {
            const contacts = await apiCall('/api/contacts');
            renderTable(contacts);
        } catch (error) {
            console.error('Chyba při načítání kontaktů:', error);
            showMessage('Chyba při načítání kontaktů: ' + error.message, false);
        }
    }

    function renderTable(contacts) {
        if (contactsDataTable) {
            contactsDataTable.destroy();
            contactsDataTable = null;
        }

        // Vyčistit tbody před novou inicializací
        contactsList.innerHTML = '';

        contactsDataTable = new DataTable(contactsTable, {
            data: contacts,
            pageLength: 25,
            columns: [
                { data: 'name', title: 'Název', className: 'text-center' },
                { data: 'email', title: 'E-mail', className: 'text-center' },
                {
                    data: 'type',
                    title: 'Typ',
                    className: 'text-center',
                    render: (data) => typeLabels[data] || data
                },
                {
                    data: 'is_active',
                    title: 'Aktivní',
                    className: 'text-center',
                    render: (data) => data
                        ? '<span class="badge bg-success">Ano</span>'
                        : '<span class="badge bg-secondary">Ne</span>'
                },
                {
                    data: 'id',
                    title: 'Akce',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: (id) => `
                        <button class="btn btn-sm btn-outline-primary edit-contact" data-id="${id}" title="Upravit">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-contact" data-id="${id}" title="Smazat">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    `
                }
            ],
            language: { url: '/assets/cs.json' },
            responsive: true,
            info: false
        });
    }

    // ============= CRUD =============

    async function saveContact(event) {
        event.preventDefault();

        const formData = new FormData(form);
        const id = formData.get('contact_id');

        const payload = {
            name:      formData.get('name'),
            email:     formData.get('email'),
            type:      formData.get('type'),
            is_active: document.getElementById('contact-active').checked,
        };

        try {
            let result;
            if (id) {
                result = await apiCall(`/api/contacts/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(payload)
                });
            } else {
                result = await apiCall('/api/contacts', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
            }

            showMessage(result.message || 'Uloženo', true);
            closeForm();
            await loadContacts();
        } catch (error) {
            console.error('Chyba při ukládání:', error);
            showMessage('Chyba při ukládání: ' + error.message, false);
        }
    }

    async function deleteContact(id) {
        if (!confirm('Opravdu smazat tento kontakt?')) return;

        try {
            const result = await apiCall(`/api/contacts/${id}`, { method: 'DELETE' });
            showMessage(result.message || 'Smazáno', true);
            await loadContacts();
        } catch (error) {
            console.error('Chyba při mazání:', error);
            showMessage('Chyba při mazání: ' + error.message, false);
        }
    }

    async function startEdit(id) {
        try {
            const contact = await apiCall(`/api/contacts/${id}`);
            fillFormForEdit(contact);
        } catch (error) {
            showMessage('Chyba při načítání kontaktu: ' + error.message, false);
        }
    }

    // ============= EVENT LISTENERY =============

    addBtn.addEventListener('click', openForm);
    cancelBtn.addEventListener('click', closeForm);
    form.addEventListener('submit', saveContact);

    // Delegovaný listener pro akce v tabulce (funguje i po rerenderu DataTable)
    contactsTable.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-contact');
        const deleteBtn = e.target.closest('.delete-contact');

        if (editBtn) {
            startEdit(editBtn.dataset.id);
        } else if (deleteBtn) {
            deleteContact(deleteBtn.dataset.id);
        }
    });

    // ============= INIT =============
    loadContacts();
}