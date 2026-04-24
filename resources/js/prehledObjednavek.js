import {Modal} from 'bootstrap';
import DataTable from 'datatables.net-dt';
import JSZip from 'jszip';
import pdfMake from 'pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

window.JSZip = JSZip;
window.pdfMake = pdfMake;
pdfMake.addVirtualFileSystem(pdfFonts);

let activitiesTable = null;
let signatureModalInstance = null;
let currentOrderId = null;
let currentRowToUpdate = null;
let blankSignatureData = null; // ZDE: Deklarace blankSignatureData


 function prehled() {
    console.log('script_objednávka.js: Modul inicializován.');

    const ordersList = document.getElementById('orders-list');
    const notificationContainer = document.getElementById('notification-container');
    const signatureModalElement = document.getElementById('signatureModal');
    const signatureCanvas = document.getElementById('signatureCanvas');
     const csrfToken = document.querySelector('meta[name="csrf-token"]');

     // Ověř kritické elementy
     if (!csrfToken) {
         console.error('CSRF token nebyl nalezen!');
         alert('Chyba: Chybí bezpečnostní token');
         return;
     }

     if (!ordersList) {
         console.error('Element #orders-list nebyl nalezen!');
         return;
     }

    const signatureCtx = signatureCanvas ? signatureCanvas.getContext('2d') : null;
    const clearSignatureBtn = document.getElementById('clearSignature');
    const confirmSignatureBtn = document.getElementById('confirmSignature');
    const closeSignatureBtn = document.querySelector('#signatureModal .btn-close');

    if (signatureModalElement) {
        signatureModalInstance = new Modal(signatureModalElement);
        console.log('Bootstrap Modal pro podpis inicializován.');
    } else {
        console.warn('Element #signatureModal nebyl nalezen. Funkce podpisu nebude fungovat.');
    }

    function showNotification(message, type = 'info') {
        if (!notificationContainer) {
            console.warn('Notification container není nalezen.');
            return;
        }
        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        notification.textContent = message;
        notificationContainer.appendChild(notification);
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }, 100);
    }

    let isDrawing = false;

    function getPosition(e, canvas) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches && e.touches.length > 0) {
            return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
        } else {
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        }
    }

    function startDrawing(e) {
        if (!signatureCtx) return;
        isDrawing = true;
        const pos = getPosition(e, signatureCanvas);
        signatureCtx.beginPath();
        signatureCtx.moveTo(pos.x, pos.y);
        // ZDE: Povolíme tlačítko při prvním tahu
        if (confirmSignatureBtn) {
            confirmSignatureBtn.disabled = false;
        }
    }

    function draw(e) {
        if (!isDrawing || !signatureCtx) return;
        e.preventDefault();
        const pos = getPosition(e, signatureCanvas);
        signatureCtx.lineWidth = 2;
        signatureCtx.lineCap = 'round';
        signatureCtx.strokeStyle = '#000';
        signatureCtx.lineTo(pos.x, pos.y);
        signatureCtx.stroke();
        signatureCtx.beginPath();
        signatureCtx.moveTo(pos.x, pos.y);
    }

    function stopDrawing() {
        if (!signatureCtx) return;
        isDrawing = false;
        signatureCtx.beginPath();
    }

    if (signatureCanvas) {
        signatureCanvas.addEventListener('mousedown', startDrawing);
        signatureCanvas.addEventListener('mousemove', draw);
        signatureCanvas.addEventListener('mouseup', stopDrawing);
        signatureCanvas.addEventListener('mouseleave', stopDrawing);
        signatureCanvas.addEventListener('touchstart', startDrawing);
        signatureCanvas.addEventListener('touchmove', draw);
        signatureCanvas.addEventListener('touchend', stopDrawing);
        signatureCanvas.addEventListener('touchcancel', stopDrawing);

        // ZDE: Inicializace blankSignatureData po vykreslení canvasu
        // Ujisti se, že canvas je prázdný, než z něj vezmeš Data URL
        setTimeout(() => {
            if (signatureCtx) {
                signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                blankSignatureData = signatureCanvas.toDataURL('image/png');
                console.log('Blank signature data initialized.');
            }
        }, 0);
    }

    if (clearSignatureBtn) {
        clearSignatureBtn.addEventListener('click', () => {
            if (signatureCtx) signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            // ZDE: Znovu zakážeme tlačítko po vymazání
            if (confirmSignatureBtn) {
                confirmSignatureBtn.disabled = true;
            }
        });
    }

    // ZDE: Sloučená a opravená obsluha closeSignatureBtn
    if (closeSignatureBtn) {
        closeSignatureBtn.addEventListener('click', () => {
            if (signatureModalInstance) {
                signatureModalInstance.hide();
                currentOrderId = null;
                currentRowToUpdate = null;
                if (signatureCtx) signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                // ZDE: Zakážeme tlačítko i při zavření modalu
                if (confirmSignatureBtn) {
                    confirmSignatureBtn.disabled = true;
                }
            }
        });
    }

    function showSignaturePad(orderId, rowElement) {
        currentOrderId = orderId;
        currentRowToUpdate = rowElement;
        if (signatureModalInstance) {
            // ZDE: Vždy vyčistíme canvas a zakážeme tlačítko při otevření modalu
            if (signatureCtx) signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            if (confirmSignatureBtn) {
                confirmSignatureBtn.disabled = true;
            }
            signatureModalInstance.show();
        } else {
            console.error('signatureModalInstance není inicializováno. Nelze zobrazit podpisový pad.');
        }
    }

    function showDeleteConfirmation(orderId, rowToDelete) {
        if (!notificationContainer) {
            console.warn('Notification container není nalezen pro potvrzení smazání.');
            return;
        }
        const confirmationDiv = document.createElement('div');
        confirmationDiv.classList.add('delete-confirmation');
        confirmationDiv.classList.add('shadow-error');
        confirmationDiv.innerHTML = `
            Opravdu si přejete objednávku odstranit?
            <button class="btn btn-sm btn-danger confirm-delete-btn" data-id="${orderId}">
                <i class="fa-solid fa-trash"></i>  Odstranit
            </button>
            <button class="btn btn-sm btn-secondary cancel-delete-btn">
                <i class="fa-solid fa-xmark"></i> Zrušit
            </button>
        `;
        notificationContainer.appendChild(confirmationDiv);

        const confirmDeleteBtn = confirmationDiv.querySelector('.confirm-delete-btn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                fetch('/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => {
                                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then(result => {
                        showNotification(result.message, 'success');
                        if (activitiesTable) {
                            activitiesTable.row(rowToDelete).remove().draw();
                        } else {
                            console.warn('DataTables instance activitiesTable není dostupná.');
                        }
                        confirmationDiv.remove();
                    })
                    .catch(error => {
                        console.error('Chyba při odstraňování objednávky', error);
                        showNotification(error.message || 'Došlo k chybě při odstraňování objednávky.', 'error');
                        confirmationDiv.remove();
                    });
            });
        }

        const cancelDeleteBtn = confirmationDiv.querySelector('.cancel-delete-btn');
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', () => {
                confirmationDiv.remove();
            });
        }
    }

    fetch('/alloders')
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.error || `Chyba serveru: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (ordersList) ordersList.innerHTML = '';
            data.forEach(order => {
                const tr = document.createElement('tr');
                const img = order.obrazek ? `<img src="/images/OOPP/${order.obrazek}" alt="Obrázek produktu" style="max-width: 150px; height: auto;">` : 'Obrázek u produktu není';
                const dateOrig = new Date(order.datum_objednani);
                const day = dateOrig.getDate();
                const month = dateOrig.getMonth() + 1;
                const year = dateOrig.getFullYear();
                const formDate = `${day}.${month}.${year}`;

                let statusHtml;
                const currentStatus = order.status.toLowerCase();

                if (currentStatus === 'cekajici') {
                    statusHtml = '<span class="status cekajici">Čekající</span>';
                } else if (currentStatus === 'objednáno') {
                    statusHtml = '<span class="status objednáno">Objednáno</span>';
                } else if (currentStatus === 'vydáno') {
                    statusHtml = '<span class="status vydáno">Vydáno</span>';
                } else {
                    statusHtml = `<span>${order.status}</span>`;
                }

                let buttonsHTML = `
                    <button class='vydat btn btn-sm btn-success me-1' data-id='${order.id}'>Vydat</button>
                    <button class="objednat btn btn-sm btn-warning me-1" data-id="${order.id}">Objednat</button>
                    <button class="odstranit btn btn-sm btn-danger" data-id="${order.id}">Odstranit</button>
                `;

                tr.innerHTML = `
                    <td>${formDate}</td>
                    <td>${order.jmeno} ${order.prijmeni}</td>
                    <td>${order.produkt}</td>
                    <td>${order.velikost}</td>
                    <td>${img}</td>
                    <td class="status-cell">${statusHtml}</td>
                    <td>${buttonsHTML}</td>
                `;
                if (ordersList) ordersList.appendChild(tr);

                if (currentStatus === 'objednáno' || currentStatus === 'vydáno') {
                    const objednatButton = tr.querySelector('.objednat');
                    if (objednatButton) {
                        objednatButton.classList.add('d-none');
                    }
                }
                if (currentStatus === 'vydáno') {
                    const vydatButton = tr.querySelector('.vydat');
                    if (vydatButton) {
                        vydatButton.classList.add('d-none');
                    }
                }
            });

            const tableElement = document.getElementById('activitiesTable');
            if (tableElement) {
                activitiesTable = new DataTable(tableElement, {
                    dom:
                        "<'row align-items-center mb-2 me-2'<'col-auto'B><'col text-end'f >>" +
                        "<'table-responsive't>" +
                        "<'row align-items-center mt-2 mb-2'<'col text-center'p>>",
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
                    ],
                    rowReorder: true,
                    paging: true,
                    searching: true,
                    info: false,
                    responsive: true,
                    language: {
                        url: "/assets/cs.json"
                    }
                });
                console.log('DataTables pro aktivity inicializováno.');
            } else {
                console.warn('Element #activitiesTable nebyl nalezen, DataTables nebude inicializováno.');
            }

            if (tableElement) {
                tableElement.addEventListener('click', function(event) {
                    const target = event.target;

                    if (target.classList.contains('vydat')) {
                        const orderId = target.dataset.id;
                        const rowElement = target.closest('tr');
                        showSignaturePad(orderId, rowElement);
                    }

                    if (target.classList.contains('objednat')) {
                        const orderId = target.dataset.id;
                        const rowToUpdate = target.closest('tr');

                        fetch('/objednat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json', // <-- ZMĚŇ
                                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ order_id: orderId }) // <-- ZMĚŇ na JSON
                        })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(errorData => {
                                        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                                    });
                                }
                                return response.json();
                            })
                            .then(result => {
                                showNotification(result.message, 'success');
                                if (result.success && activitiesTable) {
                                    const table = activitiesTable;
                                    const rowIndex = table.row(rowToUpdate).index();
                                    const statusColumnIndexLocal = Array.from(tableElement.querySelectorAll('th'))
                                        .findIndex(th => th.textContent.includes("Status"));
                                    if (statusColumnIndexLocal >= 0) {
                                        table.cell(rowIndex, statusColumnIndexLocal)
                                            .data('<span class="status objednáno">Objednáno</span>').draw(false);
                                    }
                                    target.classList.add('d-none');
                                }
                            })
                            .catch(error => {
                                console.error('Chyba objednání:', error);
                                showNotification('Chyba při objednání.', 'error');
                            });
                    }

                    if (target.classList.contains('odstranit')) {
                        const orderId = target.dataset.id;
                        const rowToDelete = target.closest('tr');
                        showDeleteConfirmation(orderId, rowToDelete);
                    }
                });
            }

            if (confirmSignatureBtn) {
                confirmSignatureBtn.addEventListener('click', () => {
                    const signatureData = signatureCanvas.toDataURL('image/png');

                    // ZDE: Kontrola prázdného podpisu
                    if (blankSignatureData && signatureData === blankSignatureData) {
                        showNotification('Prosím, nakreslete podpis.', 'error');
                        return; // Zastavíme odeslání
                    }

                    if (currentOrderId && currentRowToUpdate) {
                        fetch('/vydat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken.getAttribute('content'), // <-- PŘIDEJ TOHLE
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ order_id: currentOrderId, signature: signatureData })
                        })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(errorData => {
                                        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                                    });
                                }
                                return response.json();
                            })
                            .then(result => {
                                showNotification(result.message, 'success');
                                if (signatureModalInstance) {
                                    signatureModalInstance.hide();
                                    if (signatureCtx) signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                                    // ZDE: Po úspěšném podpisu zakážeme tlačítko
                                    if (confirmSignatureBtn) { // Přidána kontrola existence
                                        confirmSignatureBtn.disabled = true;
                                    }
                                }

                                if (activitiesTable && currentRowToUpdate) {
                                    activitiesTable.row(currentRowToUpdate).remove().draw();
                                } else {
                                    console.warn('DataTables instance nebo referenční řádek nejsou dostupné pro odstranění.');
                                }

                                currentOrderId = null;
                                currentRowToUpdate = null;
                            })
                            .catch(error => {
                                console.error('Chyba při ukládání podpisu', error);
                                showNotification('Chyba při ukládání podpisu.', 'error');
                                currentOrderId = null;
                                currentRowToUpdate = null;
                            });
                    } else {
                        showNotification('Nedošlo k výběru objednávky nebo chybí reference na řádek.', 'error');
                    }
                });
            }

        })
        .catch(error => {
            console.error('Chyba při načítání všech objednávek:', error);
            showNotification(error.message || 'Nepodařilo se načíst objednávky.', 'error');
        });
}
document.addEventListener('DOMContentLoaded', prehled);
export { prehled };
