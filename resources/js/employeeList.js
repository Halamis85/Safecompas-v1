// resources/js/employees.js
import DataTable from 'datatables.net-dt';
import JSZip from 'jszip';
import pdfMake from 'pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';


window.JSZip = JSZip;
window.pdfMake = pdfMake;
pdfMake.addVirtualFileSystem(pdfFonts);

// Proměnná pro instanci DataTables, abychom ji mohli zničit/znovu inicializovat
let employeesDataTable;
function employeeList() {
    console.log('employees.js: Script pro správu zaměstnanců inicializován.');

    const addEmployeeForm  = document.getElementById('add-employee-form');
    const emploeesTable = document.getElementById('employees-table'); // Změněno z emploeeslist
    const zpravaPri = document.getElementById('zprava-pri');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token nebyl nalezen!');
        alert('Chyba: Chybí bezpečnostní token');
        return;
    }
    function displayMessage(message, isSuccess = true) {
        if (!zpravaPri) {
            console.warn('Element pro zprávy (zpravaPri) nebyl nalezen.');
            return;
        }
        // Vyčistíme a skryjeme zprávu před zobrazením nové
        zpravaPri.textContent = '';
        zpravaPri.classList.add('d-none'); // Vždycky nejprve skryjeme

        // Odstraníme všechny předchozí styly
        zpravaPri.classList.remove('alert-success', 'alert-danger');

        // Nastavíme text a třídy
        zpravaPri.textContent = message;
        if (isSuccess) {
            zpravaPri.classList.add('alert-success');
            zpravaPri.classList.add('shadow-success');
        } else {
            zpravaPri.classList.add('alert-danger');
            zpravaPri.classList.add('shadow-error');
        }
        zpravaPri.classList.remove('d-none'); // Zobrazíme element

        // Nastavení timeout pro skrytí zprávy
        setTimeout(() => {
            zpravaPri.classList.add('d-none'); // Skryjeme element po timeout
            zpravaPri.textContent = ''; // Vyčistíme text po skrytí
            zpravaPri.classList.remove('alert-success', 'alert-danger'); // Odstraníme styly
        }, 5000); // Zpráva zmizí po 5 sekundách
    }


    // --- Logika pro přidání uživatele ---
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(addEmployeeForm);
            const payload = {};
            for (const [key, value] of formData.entries()) {
                // Zajistíme, že hodnota je vždy string.
                // Typ File je převeden na prázdný string, pokud ho nechceme posílat.
                // Pro textová pole je hodnota již string.
                payload[key] = String(value);
            }
            fetch('/employeeAdd', {
                body: new URLSearchParams(payload),
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json'
                },
            })

                .then(response => {
                    // Vždy parsujeme JSON, protože náš PHP error handler vrací JSON i pro chyby
                    return response.json().then(data => {
                        if (!response.ok) {
                            // Vytvoříme Error objekt, aby ho .catch() mohl zpracovat
                            throw new Error(data.message || 'Neznámá chyba serveru.');
                        }
                        return data; // Pro úspěšné odpovědi pokračujeme s daty
                    });
                })
                .then(data => {
                    // Zde se dostaneme jen, pokud response.ok bylo true
                    if (data.status === 'success') {
                        displayMessage('Zaměstnanec byl úspěšně přidán!', true);
                        addEmployeeForm.reset();
                        reloadEmployeesTable(); // Znovu načti tabulku
                    } else {
                        // Toto by teoreticky nemělo nastat, pokud !response.ok bylo ošetřeno výše.
                        // Ale jako fallback pro "logické" chyby s 200 OK status.
                        displayMessage('Chyba při přidání zaměstnance: ' + (data.message || 'Neznámá chyba.'), false);
                    }
                })
                .catch(error => {
                    // Zde se zachytí síťové chyby (např. server nedostupný)
                    // a chyby vyhozené v předchozích .then() blocích (např. náš `throw new Error`).
                    console.error('Chyba při přidávání zaměstnance:', error);
                    displayMessage('Chyba při přidávání zaměstnance: ' + (error.message || 'Nelze se připojit k serveru.'), false);
                });
        });
    }

    // --- Funkce pro načítání a inicializaci DataTables ---
    function loadAndInitEmployeesTable() {
        // Zničit existující instanci DataTables, pokud existuje
        if (employeesDataTable) {
            employeesDataTable.destroy();
            employeesDataTable = null;
        }

        // Vyčistit tbody před novým načtením dat, pokud to DataTables nedělá automaticky (což obvykle ano)
        const tbody = emploeesTable ? emploeesTable.querySelector('tbody') : null;
        if (tbody) {
            tbody.innerHTML = '';
        }

        fetch('/employee',)
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.message || 'Chyba při načítání zaměstnanců.');
                    }
                    return data; // Toto budou data zaměstnanců
                });
            })
            .then(employees => {
                if (emploeesTable) {
                    employeesDataTable = new DataTable(emploeesTable, {
                        data: employees,
                        pageLength: 25,
                        columns: [
                            {data: 'jmeno', title: 'Jméno',className:'dt-column-lastname'},
                            {data: 'prijmeni', title: "Příjmení", className:'dt-column-surname'},
                            {data: 'stredisko', title: 'Středisko',className:'dt-column-stredisko'},
                            {
                                data: 'id', // Sloupec 'id' je pro tlačítko
                                title: 'Akce',
                                orderable: false, // Neumožní řazení podle tohoto sloupce
                                searchable: false, // Neumožní vyhledávání v tomto sloupci
                                className: 'dt-column-akce',
                                render: function (data) {
                                    // Vytvoří HTML pro tlačítko Odebrat
                                    return `<button class="delete-employee btn delete-user" data-id="${data}">Odebrat zaměstnance</button>`;
                                }
                            }
                        ],
                        dom:
                            "<'row align-items-center mb-2 me-2'<'col-auto'B><'col text-end'f>>" + // Horní řádek
                            "<'table-responsive't>" +
                            "<'row align-items-center mt-2 mb-2'<'col text-center'p>>", // Ponecháme vestavěné vyhledávání
                        buttons: [
                            {
                                extend: 'excelHtml5', text: '<img src="/images/excel1.svg" ' +
                                    'alt="Excel" width="30">', titleAttr: 'Exportovat tabulku do Exlu'
                                , className: 'btn p-0 m-2'
                            },
                            {
                                extend: 'pdfHtml5', text: '<img src="/images/PDF.svg" ' +
                                    'alt="PDF" width="30">', titleAttr: 'Exportovat tabulku do PDF'
                                , className: 'btn p-0 m-2'
                            },
                            {
                                extend: 'print', text: '<img src="/images/printer.svg" ' +
                                    'alt="PDF" width="30">', titleAttr: 'Vytisknout tabulku '
                                , className: 'btn p-0 m-2'
                            }
                        ], // Předáváme DataTables načtená data
                        info: false,
                        language: {
                            url: "/assets/cs.json"
                        },
                        // Pokud budete chtít exportní tlačítka i zde, odkomentujte
                        paging: true, // Spodní řádek (info + stránkování)
                        responsive: true,
                        searching: true
                    });

                    // Po inicializaci DataTables musíme delegovat event listener
                    // protože se tlačítka znovu generují
                    $(emploeesTable).off('click', '.delete-employee').on('click', '.delete-employee', function () {
                        const userID = $(this).data('id'); // Získání data-id
                        handleDeleteUser(userID);
                    });

                    console.log("DataTables pro zaměstnance inicializováno.");

                } else {
                    console.warn('employees.js: Element #employees-table nebyl nalezen.');
                }
            })
            .catch(error => {
                console.error('Chyba při načítání zaměstnanců pro DataTables:', error);
                displayMessage('Chyba při načítání dat zaměstnanců: ' + (error.message || 'Nelze se připojit k serveru.'), false);
            });
    }
    // Funkce pro znovu načtení dat DataTables
    function reloadEmployeesTable() {
        loadAndInitEmployeesTable();
    }

    // --- Logika pro smazání uživatele ---
    function handleDeleteUser(userID) {
        fetch(`/employee/${userID}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
            .then(response => {
                return response.json().then(result => {
                    if (!response.ok) {
                        throw new Error(result.message || 'Chyba při odstraňování zaměstnance.');
                    }
                    return result;
                });
            })
            .then(result => {
                if (result.status === 'success') {
                    displayMessage('Zaměstnanec byl úspěšně odebrán.', true);
                    reloadEmployeesTable();
                } else {
                    displayMessage('Chyba při odebrání zaměstnance: ' + (result.message || 'Neznámá chyba.'), false);
                }
            })
            .catch(error => {
                console.error('Chyba při odstraňování zaměstnance:', error);
                displayMessage('Chyba při odstraňování zaměstnance: ' + (error.message || 'Nelze se připojit k serveru.'), false);
            });
    }
    // Inicializace tabulky při načtení stránky
    if (emploeesTable) {
        loadAndInitEmployeesTable();
    } else {
        console.warn('employees.js: Element s ID "employees-table" pro DataTables nebyl nalezen.');
    }
}
document.addEventListener("DOMContentLoaded", employeeList);
export {employeeList};
