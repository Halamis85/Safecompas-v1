{{-- Modal pro výdej materiálu z lékárničky --}}
<div class="modal fade" id="vydejMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content glass-modal border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="text-uppercase small text-white-50 fw-semibold mb-1">Výdej materiálu</div>
                    <h5 class="modal-title fw-bold fs-4">
                        <i class="fa-solid fa-hand-holding-medical me-2 text-primary"></i>Vydat materiál
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <form id="vydej-material-form">
                <input type="hidden" name="material_id" id="vydej-material-id">
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                                <div>
                                    <label class="form-label">Filtrovat podle lékárničky</label>
                                    <select class="form-select glass-input" id="vydej-lekarnicky-select">
                                        <option value="">Všechny lékárničky</option>
                                    </select>
                                </div>
                                <div class="text-white-50 small">
                                    Vyberte řádek přes tlačítko <strong class="text-white">Vydat položku</strong>.
                                </div>
                            </div>

                            <div class="table-responsive rounded-3 overflow-hidden">
                                <table id="vydejMaterialTable" class="table table-hover mb-0 w-100">
                                    <thead class="table-dark-glass">
                                    <tr>
                                        <th>Materiál</th>
                                        <th>Lékárnička</th>
                                        <th class="text-center">Skladem</th>
                                        <th>Expirace</th>
                                        <th class="text-center">Stav</th>
                                        <th class="text-end">Akce</th>
                                    </tr>
                                    </thead>
                                    <tbody id="vydej-material-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="vydej-selection-panel">
                                <span class="detail-info-label"><i class="fa-solid fa-hand-holding-medical"></i> Vybraná položka</span>
                                <strong id="vydej-selected-material">Zatím není vybráno</strong>
                                <small id="vydej-selected-location">Vyberte materiál z tabulky.</small>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label">K dispozici</label>
                                <input type="text" class="form-control glass-input" id="vydej-material-stock" value="—" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Množství *</label>
                                <input type="number" class="form-control glass-input" name="vydane_mnozstvi" min="1" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Záznam úrazu</label>
                                <select class="form-select glass-input" name="uraz_id" id="vydej-uraz-select">
                                    <option value="">Bez záznamu úrazu</option>
                                </select>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="objednat_po_vydeji" value="1" id="vydej-order-after-issue" checked>
                                <label class="form-check-label" for="vydej-order-after-issue">
                                    Objednat doplnění ve stejném množství
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Osoba vydávající</label>
                                <input type="text"
                                       class="form-control glass-input"
                                       value="{{ trim((session('user.firstname') ?? '') . ' ' . (session('user.lastname') ?? '')) ?: (session('user.username') ?? session('user.email') ?? 'Přihlášený uživatel') }}"
                                       readonly>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Poznámky</label>
                                <textarea class="form-control glass-input" name="poznamky" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check me-1"></i> Vydat materiál
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
