import DataTable from 'datatables.net'; // Základní DataTables
import 'datatables.net-buttons';
import 'datatables.net-buttons/js/buttons.html5.js';
import 'datatables.net-buttons/js/buttons.print.js'
import JSZip from "jszip";
import pdfMake from 'pdfmake'; // Potřeba pro PDF export
import pdfFonts from "pdfmake/build/vfs_fonts";

pdfMake.addVirtualFileSystem(pdfFonts);

window.JSZip = JSZip;
window.pdfMake = pdfMake;

function displayMessage(message, isSuccess = true) {
    const zpravaPri = document.getElementById('zprava-pri');
    if (!zpravaPri) {
        console.warn('script_card.js: Element pro zprávy (zpravaPri) nebyl nalezen. Zpráva: ' + message);
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
    } else {
        zpravaPri.classList.add('alert-danger');
    }
    zpravaPri.classList.remove('d-none'); // Zobrazíme element

    // Nastavení timeoutu pro skrytí zprávy
    setTimeout(() => {
        zpravaPri.classList.add('d-none'); // Skryjeme element po timeoutu
        zpravaPri.textContent = ''; // Vyčistíme text po skrytí
        zpravaPri.classList.remove('alert-success', 'alert-danger'); // Odstraníme styly
    }, 5000); // Zpráva zmizí po 5 sekundách
}

// Helper pro zpracování fetch odpovědí
async function handleFetchResponse(response) {
    let rawData;
    try {
        rawData = await response.json();
    } catch (e) {
        throw new Error(`Odpověď ze serveru nebyla platný JSON. Status: ${response.status}. Chyba: ${e.message}`);
    }

    if (!response.ok) {
        // Zde se stále snažíme získat message, pokud je k dispozici
        const errorMessage = rawData.message || rawData.error || `Chyba serveru: ${response.status}`;
        throw new Error(errorMessage);
    }

    // *** ZDE JE NEJdůLEŽITĚJŠÍ ZMĚNA: Tolerance pro různé formáty dat ***
    // Pokud je rawData objekt s klíčem 'data' a statusem 'success', vrátíme data.
    if (typeof rawData === 'object' && rawData !== null && rawData.status === 'success' && rawData.hasOwnProperty('data') && Array.isArray(rawData.data)) {
        return rawData.data; // Vracíme přímo pole objednávek/zaměstnanců z 'data' klíče
    }
    // Pokud rawData je přímo pole (jako u autocomplete endpointu)
    else if (Array.isArray(rawData)) {
        return rawData; // Vracíme přímo pole
    }
    // Jinak neznámý formát
    else {
        console.warn("handleFetchResponse: Neočekávaný formát JSON odpovědi. rawData:", rawData);
        throw new Error('Neočekávaný formát dat z API. Odpověď by měla být pole nebo objekt s klíčem "data" a "status": "success".');
    }
}

 function cardEmploee() {


    const zamestnanecInput = document.getElementById('zamestnanec');
    const zamestnanecList = document.getElementById('zamestnanec-list');
    const autocompleteContainer = document.querySelector('.autocomplete-container'); // Nově získaný kontejner pro autocomplete
    const tableContainer = document.getElementById('table-container');
    const closeB = document.getElementById('closeB');
    const selectedEmployee = document.getElementById('selected-employee');
    let currentFocus = -1;

    // Skrytí kontejneru a tlačítka zavřít na začátku (kontejner bude skryt, dokud se nezobrazí tabulka)
    tableContainer.classList.add('d-none');
    closeB.classList.add('d-none');
    selectedEmployee.classList.add('d-none');
    zamestnanecList.classList.add('d-none'); // Ujistíme se, že autocomplete list je na začátku skrytý

    // --- Funkce pro vyhledávání a autocomplete ---
    zamestnanecInput.addEventListener('input', () => {
        const query = zamestnanecInput.value;
        if (query.length > 1) {
            // Zobrazíme list-group, jakmile začneme psát
            zamestnanecList.classList.remove('d-none');
            fetch(`/zamestnanci?q=${encodeURIComponent(query)}`)
                .then(handleFetchResponse)
                .then(data => {
                    zamestnanecList.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(zamestnanec => {
                            const div = document.createElement('a');
                            div.className = 'list-group-item list-group-item-action';
                            div.textContent = `${zamestnanec.jmeno} ${zamestnanec.prijmeni}`;
                            div.dataset.id = zamestnanec.id;
                            div.addEventListener('click', () => {
                                handleZamestnanecSelect(zamestnanec.id, zamestnanec.jmeno, zamestnanec.prijmeni);
                                zamestnanecList.classList.add('d-none'); // Skryjeme po výběru
                            });
                            zamestnanecList.appendChild(div);
                        });
                    } else {
                        zamestnanecList.innerHTML = '<div class="list-group-item">Žádní zaměstnanci nebyli nalezeni.</div>';
                    }
                })
                .catch(error => {
                    console.error('Chyba při vyhledávání zaměstnanců:', error);
                    displayMessage('Chyba při vyhledávání zaměstnanců: ' + (error.message || 'Nelze se připojit k serveru.'), false);
                    zamestnanecList.classList.add('d-none'); // Skryjeme list i při chybě
                });
        } else {
            // Skryjeme list-group, pokud je query příliš krátká nebo prázdná
            zamestnanecList.innerHTML = '';
            zamestnanecList.classList.add('d-none');
        }
    });

    zamestnanecInput.addEventListener('keydown', (e) => {
        const items = zamestnanecList.getElementsByClassName('list-group-item');
        if (e.key === 'ArrowDown') {
            currentFocus++;
            addActive(items);
        } else if (e.key === 'ArrowUp') {
            currentFocus--;
            addActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentFocus > -1 && items[currentFocus]) {
                items[currentFocus].click();
            }
        }
        // Zobrazíme list-group i při navigaci šipkami, pokud je skrytý a jsou tam položky
        if (items.length > 0 && zamestnanecList.classList.contains('d-none')) {
            zamestnanecList.classList.remove('d-none');
        }
    });

    function addActive(items) {
        if (!items || items.length === 0) return; // Přidána kontrola
        removeActive(items);
        if (currentFocus >= items.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = items.length - 1;
        items[currentFocus].classList.add('active');
        items[currentFocus].scrollIntoView({ block: "nearest" });
    }

    function removeActive(items) {
        for (let i = 0; i < items.length; i++) {
            items[i].classList.remove('active');
        }
    }

    // --- Skrytí list-group po kliknutí mimo ---
    document.addEventListener('click', (event) => {
        // Kontrolujeme, zda kliknutí nebylo uvnitř kontejneru autocomplete nebo na samotný input
        if (autocompleteContainer && !autocompleteContainer.contains(event.target)) {
            zamestnanecList.classList.add('d-none');
            currentFocus = -1; // Reset focus
        }
    });

    // --- Listener pro tlačítko "Zavřít" ---
    closeB.addEventListener('click', () => {

        // Jednoduše vyprázdníme a skryjeme kontejner, čímž odstraníme celou tabulku
        tableContainer.innerHTML = '';
        tableContainer.classList.add('d-none');

        // Vyčisti a resetuj ostatní UI prvky
        zamestnanecList.innerHTML = '';
        zamestnanecInput.value = '';
        zamestnanecInput.disabled = false;
        zamestnanecInput.focus();
        selectedEmployee.innerHTML = '';
        selectedEmployee.classList.add('d-none');
        closeB.classList.add('d-none');
        zamestnanecList.classList.add('d-none'); // Zajistíme, že se list skryje i po zavření tabulky
    });

    // --- Funkce pro výběr zaměstnance a zobrazení tabulky ---
    function handleZamestnanecSelect(zamestnanecId, jmeno, prijemni) {

        // KROK 1: Vyčisti a skryj starou tabulku (pokud existuje)
        tableContainer.innerHTML = ''; // Vyprázdní kontejner, odstraní starou tabulku
        tableContainer.classList.remove('d-none'); // Zobrazí kontejner pro novou tabulku

        // Aktualizuj UI prvky formuláře
        zamestnanecInput.disabled = true;
        zamestnanecList.innerHTML = '';
        zamestnanecList.classList.add('d-none'); // Skryj list po výběru
        closeB.classList.remove('d-none');
        selectedEmployee.innerHTML = `<div class="cards-text-info">Zaměstnanci
            <span class="cards-text-info-bold">${jmeno} ${prijemni}</span> bylo přiděleno:</div>`;
        selectedEmployee.classList.remove('d-none');

        // KROK 2: Vytvoř novou HTML strukturu tabulky od základu
        const newTable = document.createElement('table');
        newTable.id = 'table-prh'; // Důležité: ID pro DataTables
        newTable.className = 'table table-responsive bg-light table-striped table-hover\n' +
            '                 text-center '; // Třídy pro Bootstrap/CSS
        newTable.style.width = '100%'; // Důležité pro DataTables layout

        newTable.innerHTML = `
            <thead>
                <tr>
                    <th class="text-center">Produkt</th>
                    <th class="text-center">Velikost</th>
                    <th class="text-center">Datum vydání</th>
                    <th class="text-center">Podpis</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        `;

        tableContainer.appendChild(newTable); // Vlož novou tabulku do kontejneru
        const orderListBody = newTable.querySelector('tbody'); // Získej tbody z nově vytvořené tabulky

        // KROK 3: Načti data z API a inicializuj DataTables na NOVÉ tabulce
        fetch(`zamestnanci/${zamestnanecId}/objednavky-vydane`)
            .then(handleFetchResponse)
            .then(orders => {
                if (orders.length > 0) {
                    orders.forEach(order => {
                        const row = orderListBody.insertRow();
                        row.insertCell().textContent = order.produkt;
                        row.insertCell().textContent = order.velikost;
                        row.insertCell().textContent = order.datum_vydani;
                        const podpisCell = row.insertCell();
                        podpisCell.innerHTML = order.podpis_path
                            ? `<img src="/images/signatures/${order.podpis_path}" alt="Podpis" style="max-height: 80px;">`
                            : '';
                    });
                    console.log("Data vložena do tabulky.");

                    // Inicializuj DataTables na NOVÉ tabulce
                    // Použijte nový DataTable('#table-prh')
                    new DataTable('#table-prh', {
                        rowReorder: true, // Oprava typo: rowRecoder -> rowReorder
                        paging: true,
                        searching: true,
                        info: false,
                        responsive: true,
                        dom:
                            "<'row align-items-center me-2'<'col-auto'B><'col text-end'f mb-2>>" + // Horní řádek
                            "<'table-responsive't>" +
                            "<'row align-items-center mt-2 mb-2'<'col text-center'p>>", // Ponecháme vestavěné vyhledávání
                        buttons: [
                            {
                                extend: 'excelHtml5', text: '<img src="/images/excel%20(2).svg" ' +
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
                        language: {
                            url: "/assets/cs.json"
                        }
                    });

                    console.log("DataTables inicializováno na nové tabulce.");
                } else {
                    // Pokud nejsou objednávky, zobraz zprávu přímo v tbody a DataTables neinicializuj
                    orderListBody.innerHTML = '<tr><td colspan="4">Žádné objednávky nebyly nalezeny</td></tr>';
                    console.log("Žádné objednávky nebyly nalezeny.");
                    // Tabulka zůstane obyčejná HTML tabulka
                }
            })
            .catch(error => {
                console.error('Chyba při načítání objednávek:', error);
                displayMessage('Chyba při načítání objednávek pro zaměstnance: ' + (error.message || 'Nelze se připojit k serveru.'), false);
            });
    }
}
document.addEventListener('DOMContentLoaded', cardEmploee);
export { cardEmploee };
