import {Modal} from 'bootstrap';
import DataTable from 'datatables.net-dt';

let activitiesTable = null;
let signatureModalInstance = null;
let currentOrderId = null;
let currentRowToUpdate = null;
let blankSignatureData = null; // ZDE: Deklarace blankSignatureData


function prehled() {

    const ordersList = document.getElementById('orders-list');
    const notificationContainer = document.getElementById('notification-container');
    const signatureModalElement = document.getElementById('signatureModal');
    const signatureCanvas = document.getElementById('signatureCanvas');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');

    if (!csrfToken) {
        console.error('CSRF token nebyl nalezen!');
        return;
    }

    if (!ordersList) {
        return;
    }

    const signatureCtx = signatureCanvas ? signatureCanvas.getContext('2d') : null;
    const clearSignatureBtn = document.getElementById('clearSignature');
    const confirmSignatureBtn = document.getElementById('confirmSignature');
    const closeSignatureBtn = document.querySelector('#signatureModal .btn-close');

    if (signatureModalElement) {
        signatureModalInstance = new Modal(signatureModalElement);
    }

    function showNotification(message, type = 'info') {
        if (!notificationContainer) return;
        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        
        let icon = 'fa-info-circle';
        if (type === 'success') icon = 'fa-circle-check';
        if (type === 'error') icon = 'fa-circle-exclamation';
        
        notification.innerHTML = `<i class="fa-solid ${icon}"></i> <div>${message}</div>`;
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

        setTimeout(() => {
            if (signatureCtx) {
                signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                blankSignatureData = signatureCanvas.toDataURL('image/png');
            }
        }, 0);
    }

    if (clearSignatureBtn) {
        clearSignatureBtn.addEventListener('click', () => {
            if (signatureCtx) signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            if (confirmSignatureBtn) {
                confirmSignatureBtn.disabled = true;
            }
        });
    }

    if (closeSignatureBtn) {
        closeSignatureBtn.addEventListener('click', () => {
            if (signatureModalInstance) {
                signatureModalInstance.hide();
                currentOrderId = null;
                currentRowToUpdate = null;
                if (signatureCtx) signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
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
            if (signatureCtx) signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            if (confirmSignatureBtn) {
                confirmSignatureBtn.disabled = true;
            }
            signatureModalInstance.show();
        }
    }

    function showDeleteConfirmation(orderId, rowToDelete) {
        if (!notificationContainer) return;
        const confirmationDiv = document.createElement('div');
        confirmationDiv.classList.add('delete-confirmation');
        confirmationDiv.classList.add('shadow-error');
        confirmationDiv.innerHTML = `
            Opravdu si přejete objednávku odstranit?
            <div class="mt-2">
                <button class="btn btn-sm btn-danger confirm-delete-btn" data-id="${orderId}">
                    <i class="fa-solid fa-trash"></i> Odstranit
                </button>
                <button class="btn btn-sm btn-secondary cancel-delete-btn">
                    <i class="fa-solid fa-xmark"></i> Zrušit
                </button>
            </div>
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
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        showNotification(result.message, 'success');
                        if (activitiesTable) {
                            activitiesTable.row(rowToDelete).remove().draw();
                        }
                    } else {
                        showNotification(result.error || 'Chyba při mazání.', 'error');
                    }
                    confirmationDiv.remove();
                })
                .catch(() => {
                    showNotification('Chyba při komunikaci se serverem.', 'error');
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
        .then(response => response.json())
        .then(data => {
            if (ordersList) ordersList.innerHTML = '';
            data.forEach(order => {
                const tr = document.createElement('tr');
                const img = order.obrazek ? `<img src="/images/OOPP/${order.obrazek.trim()}" alt="Produkt" style="max-width: 100px; height: auto; border-radius: var(--radius-sm);">` : 'Bez obrázku';
                const dateOrig = new Date(order.datum_objednani);
                const formDate = dateOrig.toLocaleDateString('cs-CZ');

                let statusHtml;
                const currentStatus = order.status.toLowerCase();

                if (currentStatus === 'cekajici') {
                    statusHtml = '<span class="status cekajici">Čekající</span>';
                } else if (currentStatus === 'objednáno' || currentStatus === 'objednano') {
                    statusHtml = '<span class="status objednáno">Objednáno</span>';
                } else if (currentStatus === 'vydáno' || currentStatus === 'vydano') {
                    statusHtml = '<span class="status vydáno">Vydáno</span>';
                } else {
                    statusHtml = `<span>${order.status}</span>`;
                }

                const buttonsHTML = `
                    <button class='vydat btn btn-sm btn-success me-1 ${ (currentStatus === 'vydáno' || currentStatus === 'vydano') ? 'd-none' : '' }' data-id='${order.id}'>Vydat</button>
                    <button class="objednat btn btn-sm btn-warning me-1 ${ (currentStatus !== 'cekajici') ? 'd-none' : '' }" data-id="${order.id}">Objednat</button>
                    <button class="odstranit btn btn-sm btn-danger" data-id="${order.id}">Odstranit</button>
                `;

                tr.innerHTML = `
                    <td class="text-center">${formDate}</td>
                    <td>${order.jmeno} ${order.prijmeni}</td>
                    <td>${order.produkt}</td>
                    <td class="text-center">${order.velikost}</td>
                    <td class="text-center">${order.pocet_kusu ?? 1}</td>
                    <td class="text-center">${img}</td>
                    <td class="text-center status-cell">${statusHtml}</td>
                    <td class="text-center">${buttonsHTML}</td>
                `;
                if (ordersList) ordersList.appendChild(tr);
            });

            const tableElement = document.getElementById('activitiesTable');
            if (tableElement) {
                activitiesTable = new DataTable(tableElement, {
                    paging: true,
                    searching: true,
                    info: false,
                    responsive: true,
                    language: {
                        url: "/assets/cs.json"
                    }
                });
            }

            if (tableElement) {
                tableElement.addEventListener('click', function(event) {
                    const target = event.target.closest('button');
                    if (!target) return;

                    if (target.classList.contains('vydat')) {
                        showSignaturePad(target.dataset.id, target.closest('tr'));
                    }

                    if (target.classList.contains('objednat')) {
                        const orderId = target.dataset.id;
                        const rowToUpdate = target.closest('tr');

                        fetch('/objednat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ order_id: orderId })
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.status === 'success') {
                                showNotification(result.message, 'success');
                                if (activitiesTable) {
                                    const row = activitiesTable.row(rowToUpdate);
                                    const statusCell = row.node().querySelector('.status-cell');
                                    if (statusCell) {
                                        statusCell.innerHTML = '<span class="status objednáno">Objednáno</span>';
                                        row.invalidate().draw(false);
                                    }
                                    target.classList.add('d-none');
                                }
                            } else {
                                showNotification(result.error || 'Chyba při objednání.', 'error');
                            }
                        })
                        .catch(() => {
                            showNotification('Chyba při komunikaci se serverem.', 'error');
                        });
                    }

                    if (target.classList.contains('odstranit')) {
                        showDeleteConfirmation(target.dataset.id, target.closest('tr'));
                    }
                });
            }

            if (confirmSignatureBtn) {
                confirmSignatureBtn.addEventListener('click', () => {
                    const signatureData = signatureCanvas.toDataURL('image/png');

                    if (blankSignatureData && signatureData === blankSignatureData) {
                        showNotification('Prosím, nakreslete podpis.', 'error');
                        return;
                    }

                    if (currentOrderId && currentRowToUpdate) {
                        fetch('/vydat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ order_id: currentOrderId, signature: signatureData })
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.status === 'success') {
                                showNotification('Objednávka byla úspěšně předána.', 'success');
                                if (signatureModalInstance) signatureModalInstance.hide();
                                if (activitiesTable && currentRowToUpdate) {
                                    activitiesTable.row(currentRowToUpdate).remove().draw();
                                }
                            } else {
                                showNotification(result.error || 'Chyba při ukládání.', 'error');
                            }
                            currentOrderId = null;
                            currentRowToUpdate = null;
                        })
                        .catch(() => {
                            showNotification('Chyba při komunikaci se serverem.', 'error');
                        });
                    }
                });
            }

        })
        .catch(error => {
            console.error('Chyba při načítání objednávek:', error);
            showNotification('Nepodařilo se načíst objednávky.', 'error');
        });
}
export { prehled };
