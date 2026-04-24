
// Konstanty pro HTML elementy
const datumInput = document.getElementById('datum');
// Ponecháváme tyto jako globální proměnné, ale jejich obsah se bude dynamicky měnit
let bubbleContent = document.getElementById('produkt-obrazek');
let lastInfo = document.getElementById('last-info');

const zamestnanecInput = document.getElementById('zamestnanec');
const zamestnanecList = document.getElementById('zamestnanec-list');
const autocompleteContainer = document.querySelector('.autocomplete-container');
const cardCircle = document.querySelector('.card-circle-new-oopp');
const druhSelect = document.getElementById('druh');
const produktSelect = document.getElementById('produkt');
const velikostSelect = document.getElementById('velikost');
const objednavkaForm = document.getElementById('objednavka-form');


let currentFocus = -1;
let items = [];

const csrfToken = document.querySelector('meta[name="csrf-token"]');

// Funkce pro resetování cardCircle do výchozího stavu
function resetCardCircleContent() {
    cardCircle.classList.remove('shadow-success', 'shadow-error');
    cardCircle.classList.add('shadow');
    // Znovu vytvoříme VŠECHNY DĚTI cardCircle s jejich ID
    cardCircle.innerHTML = `
        <div id="produkt-obrazek" class="mb-2"></div>
        <div id="last-info" class="text-center"></div>
    `;

    // !!! DŮLEŽITÉ: Znovu získáme reference na nově vytvořené elementy s ID !!!
    bubbleContent = document.getElementById('produkt-obrazek');
    lastInfo = document.getElementById('last-info');

}

// Funkce pro zobrazení zprávy (success/error/info) v card-circle
function displayCardMessage(iconClass, iconWeight, textColorClass, messageTitle, messageDetail, type = 'success') {
    cardCircle.innerHTML = ''; // Vyčistíme obsah - Tím se odstraní staré elementy!
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('text-center');
    messageDiv.innerHTML = `
    <i class="${iconClass} ${iconWeight} ${textColorClass} mb-2"></i>
    <p class="mb-0">${messageTitle}</p>
    ${messageDetail ? `<p class="mb-0">${messageDetail}</p>` : ''}
      `;
    cardCircle.appendChild(messageDiv);

    if (type === 'success') {
        cardCircle.classList.add('shadow-success');
        cardCircle.classList.remove('shadow');
    } else {
        cardCircle.classList.add('shadow-error');
        cardCircle.classList.remove('shadow');
    }

    // Timer pro kompletní reset formuláře a UI po zprávě
    setTimeout(() => {
        // 1.Reset formuláře na výchozí hodnoty
        objednavkaForm.reset();

        // 2.Vyčištění a reset skrytých/dataset hodnot
        zamestnanecInput.dataset.id = '';
        document.getElementById('stredisko').value = '';

        // 3.Reset dropdown a jejich obsahu na výchozí stav
        druhSelect.value = '';
        produktSelect.innerHTML = '<option value="">Vyberte produkt</option>';
        produktSelect.value = '';
        velikostSelect.innerHTML = '<option value="">Nejprve vyberte produkt</option>';
        velikostSelect.value = '';

        // 4.Reset data na aktuální datum
        datumInput.value = new Date().toISOString().split('T')[0];

        // 5.Reset cardCircle do jeho normálního stavu - Zde je klíčová změna!
        resetCardCircleContent(); // Voláme funkci, která znovu vytvoří elementy a získá nové reference

        // 6.Povolení vstupního pole pro zaměstnance a nastavení focus
        zamestnanecInput.disabled = false;
        zamestnanecInput.focus();
        zamestnanecList.classList.add('d-none');

    }, 5000);
}


function objednavka() {
    console.log('script_objednávka.js: Modul inicializován.');

    if (!csrfToken) {
        console.error('CSRF token nebyl nalezen!');
        alert('Chyba: Chybí bezpečnostní token');
        return; // ✅ Nyní je return uvnitř funkce
    }

    // Inicializace
    datumInput.value = new Date().toISOString().split('T')[0];
    zamestnanecList.classList.add('d-none');

    // Zajištění počátečního stavu cardCircle hned při inicializaci
    resetCardCircleContent();

    // --- Událost pro vyhledávací pole ---
    zamestnanecInput.addEventListener('input', () => {
        // Okamžitý reset cardCircle, pokud uživatel začne psát
        resetCardCircleContent(); // Znovu vytvoří elementy a získá nové reference!

        const query = zamestnanecInput.value;
        if (query.length > 1) {
            zamestnanecList.classList.remove('d-none');
            fetch(`/zamestnanci?q=${encodeURIComponent(query)}`)
                .then(response => response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.message || 'Chyba při komunikaci se serverem.');
                    }
                    return data;
                }))
                .then(data => {
                    zamestnanecList.innerHTML = '';
                    currentFocus = -1;
                    if (data.length > 0) {
                        data.forEach(zamestnanec => {
                            const div = document.createElement('div');
                            div.classList.add('list-group-item', 'list-group-item-action');
                            div.textContent = `${zamestnanec.jmeno} ${zamestnanec.prijmeni}`;
                            div.dataset.id = zamestnanec.id;
                            div.dataset.stredisko = zamestnanec.stredisko;
                            div.addEventListener('click', () => {
                                zamestnanecInput.value = div.textContent;
                                zamestnanecInput.dataset.id = div.dataset.id;
                                document.getElementById('stredisko').value = div.dataset.stredisko;
                                zamestnanecList.innerHTML = '';
                                zamestnanecList.classList.add('d-none');
                                // Po výběru zaměstnance je nutné resetovat cardCircle,
                                // aby se zobrazil nový obrázek a historie.
                                resetCardCircleContent(); // Důležité!
                                checkLastReceived();
                            });
                            zamestnanecList.appendChild(div);
                        });
                        items = zamestnanecList.getElementsByClassName('list-group-item');
                    } else {
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.classList.add('list-group-item', 'disabled');
                        noResultsDiv.textContent = 'Žádní zaměstnanci nebyli nalezeni.';
                        zamestnanecList.appendChild(noResultsDiv);
                        items = [];
                    }
                })
                .catch(error => {
                    console.error('Chyba při vyhledávání zaměstnanců:', error);
                    zamestnanecList.innerHTML = '<div class="list-group-item disabled">Chyba při načítání.</div>';
                    items = [];
                    zamestnanecList.classList.add('d-none');
                });
        } else {
            zamestnanecList.innerHTML = '';
            items = [];
            zamestnanecList.classList.add('d-none');
            // Okamžitý reset cardCircle, pokud se input vyprázdní
            // Toto volání je klíčové, aby se bublina resetovala, když uživatel smaže text
            resetCardCircleContent();
        }
    });

    zamestnanecInput.addEventListener('keydown', (e) => {
        const currentItems = zamestnanecList.getElementsByClassName('list-group-item');
        if (currentItems.length === 0) {
            currentFocus = -1;
            return;
        }

        if (e.key === 'ArrowDown') {
            currentFocus++;
            addActive(currentItems);
            e.preventDefault();
        } else if (e.key === 'ArrowUp') {
            currentFocus--;
            addActive(currentItems);
            e.preventDefault();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentFocus > -1 && currentItems[currentFocus]) {
                currentItems[currentFocus].click();
            }
        }
        if (currentItems.length > 0 && zamestnanecList.classList.contains('d-none')) {
            zamestnanecList.classList.remove('d-none');
        }
    });

    function addActive(items) {
        if (!items || items.length === 0) return;
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

    document.addEventListener('click', (event) => {
        if (autocompleteContainer && !autocompleteContainer.contains(event.target)) {
            zamestnanecList.classList.add('d-none');
            currentFocus = -1;
        }
    });


    fetch('/druhy')
        .then(response => response.json().then(data => {
            if (!response.ok) {
                throw new Error(data.message || 'Chyba při načítání druhů OOPP.');
            }
            return data;
        }))
        .then(data => {
            druhSelect.innerHTML = '<option value="">Vyberte druh OOPP</option>';
            data.forEach(druh => {
                const option = document.createElement('option');
                option.value = druh.id;
                option.textContent = druh.nazev;
                druhSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Chyba při načítání druhů OOPP:', error);
        });

    druhSelect.addEventListener('change', () => {
        // Okamžitý reset cardCircle při změně druhu
        resetCardCircleContent(); // Znovu vytvoří elementy a získá nové reference!

        const druhId = druhSelect.value;
        if (druhId) {
            fetch(`/druhy/${druhId}/produkty`)
                .then(response => response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.message || 'Chyba při načítání produktů pro daný druh.');
                    }
                    return data;
                }))
                .then(data => {
                    produktSelect.innerHTML = '<option value="">Vyberte produkt</option>';
                    data.forEach(produkt => {
                        const option = document.createElement('option');
                        option.value = produkt.id;
                        option.textContent = produkt.nazev;
                        produktSelect.appendChild(option);
                    });
                    produktSelect.value = '';
                    velikostSelect.innerHTML = '<option value="">Nejprve vyberte produkt</option>';
                    velikostSelect.value = '';
                })
                .catch(error => {
                    console.error('Chyba při načítání produktů:', error);
                    produktSelect.innerHTML = '<option value="">Chyba načítání produktů</option>';
                    velikostSelect.innerHTML = '<option value="">Chyba načítání velikostí</option>';
                });
        } else {
            produktSelect.innerHTML = '<option value="">Nejprve vyberte druh</option>';
            produktSelect.value = '';
            velikostSelect.innerHTML = '<option value="">Nejprve vyberte produkt</option>';
            velikostSelect.value = '';
            // Okamžitý reset cardCircle při zrušení výběru druhu
            resetCardCircleContent(); // Znovu vytvoří elementy a získá nové reference!
        }
    });

    produktSelect.addEventListener('change', () => {
        // Okamžitý reset cardCircle při změně produktu
        resetCardCircleContent(); // Znovu vytvoří elementy a získá nové reference!

        const produktId = produktSelect.value;
        if (produktId) {
            fetch(`/produkty/${produktId}`)
                .then(response => response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.message || 'Chyba při načítání detailů produktu.');
                    }
                    return data;
                }))
                .then(data => {
                    // bubbleContent a lastInfo jsou již re-referendová v resetCardCircleContent()
                    bubbleContent.innerHTML = ''; // vyčistíme, aby se mohl vložit obrázek

                    if (data.obrazek) {
                        const img = document.createElement('img');
                        img.src = `/images/OOPP/${data.obrazek}`;
                        img.alt = data.nazev;
                        bubbleContent.appendChild(img);
                    } else {
                        bubbleContent.textContent = 'Obrázek není k dispozici.';
                    }

                    velikostSelect.innerHTML = '<option value="">Vyberte velikost</option>';

                    if (Array.isArray(data.dostupne_velikosti)) {
                        data.dostupne_velikosti.forEach(velikost => {
                            const option = document.createElement('option');
                            option.value = velikost;
                            option.textContent = velikost;
                            velikostSelect.appendChild(option);
                        });
                    } else {
                        console.warn('Produkty: dostupné velikosti nejsou pole:', data.dostupne_velikosti);
                        velikostSelect.innerHTML = '<option value="">Velikosti nejsou k dispozici</option>';
                    }

                    checkLastReceived();
                })
                .catch(error => {
                    console.error('Chyba při načítání detailů produktu:', error);
                    bubbleContent.textContent = 'Chyba při načítání obrázku.';
                    velikostSelect.innerHTML = '<option value="">Chyba načítání velikostí</option>';
                    lastInfo.innerHTML = '';
                    lastInfo.textContent = 'Chyba načítání historie.';
                });
        } else {
            // Zde je také důležité volat resetCardCircleContent, když uživatel zruší výběr produktu
            resetCardCircleContent();
        }
    });

    function checkLastReceived() {
        const zamestnanecId = zamestnanecInput.dataset.id;
        const produktId = produktSelect.value;

        // Pokud se ID změnily na prázdné kvůli resets, neprovádíme fetch
        if (!zamestnanecId || !produktId) {
            lastInfo.innerHTML = '';
            return; // Důležité: Ukončíme funkci
        }

        fetch(`/last-info?zamestnanec_id=${zamestnanecId}&produkt_id=${produktId}`)
            .then(response => response.json().then(data => {
                if (!response.ok) {
                    throw new Error(data.message || 'Chyba při načítání informací o posledním výdeji.');
                }
                return data;
            }))
            .then(data => {
                // lastInfo je již re-referendová v resetCardCircleContent()
                if (data.success) {
                    const lastReceivedData = data.last_received;
                    lastInfo.innerHTML = `
                        <div class="last-info-1 text ">Poslední vydaná velikost:
                        <span class="fw-bold">${lastReceivedData.velikost } </span></div>
                        <div class="last-info">Datum vydání:
                        <span class="fw-bold">${lastReceivedData.datum_vydani || 'Nevydáno'}</span></div>
                    `;
                } else {
                    lastInfo.textContent = data.message || 'Žádný výdej nenalezen.';
                }
            })
            .catch(error => {
                console.error('Chyba při načítání posledního výdeje:', error);
                lastInfo.textContent = 'Chyba při načítání dat.';
            });
    }

    objednavkaForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const zamestnanecId = zamestnanecInput.dataset.id;
        const produktId = produktSelect.value;
        const velikost = velikostSelect.value;
        const datum = datumInput.value;

        if (!zamestnanecId || !produktId || !velikost) {
            displayCardMessage('fas fa-exclamation-triangle', 'fa-4x', 'text-warning', 'Chyba!', 'Vyplňte všechna povinná pole.', 'error');
            return;
        }



        const data = {
            zamestnanec_id: zamestnanecId,
            produkt_id: produktId,
            velikost: velikost,
            datum_objednani: datum
        };

        fetch('/odeslat-objednavku', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'), // <-- PŘIDEJ TOHLE
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)

        })
            .then(response => {
                return response.json().then(result => {
                    if (!response.ok) {
                        throw new Error(result.message || 'Neznámá chyba serveru.');
                    }
                    return result;
                });
            })
            .then(result => {
                if (result.status === 'success') {
                    displayCardMessage('fa-solid fa-circle-check', 'fa-10x', 'text-success', 'Objednávka<br>vytvořena', null, 'success');
                } else {
                    displayCardMessage('fas fa-times-circle', 'fa-4', 'text-danger', 'Chyba!', result.message || 'Nepodařilo se vytvořit objednávku.', 'error');
                    console.error('API vrátilo úspěšný status, ale result.status není "success":', result);
                }
            })
            .catch(error => {
                console.error('Chyba při odesílání objednávky:', error);
                displayCardMessage('fas fa-exclamation-triangle', 'fa-4x', 'text-warning', 'Chyba!', error.message || 'Nelze se připojit k serveru.', 'error');
            });
    });
    }
    document.addEventListener('DOMContentLoaded', objednavka);
    export { objednavka };
