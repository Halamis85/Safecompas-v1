{{-- Prostorný detail lékárničky --}}
<div class="modal fade detail-lekarnicky-modal" id="detailLekarnickyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content glass-modal border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="text-uppercase small text-white-60 fw-semibold mb-1">Detail lékárničky</div>
                    <h5 class="modal-title fw-primary fs-4" id="detail-lekarnicky-title">
                        <i class="fa-solid fa-kit-medical me-2 text-primary"></i>Lékárnička
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>

            <div class="modal-body p-4">
                <div class="detail-lekarnicky-summary mb-4">
                    <div class="detail-info-item">
                        <span class="detail-info-label"><i class="fa-solid fa-location-dot"></i> Umístění</span>
                        <strong id="detail-umisteni">—</strong>
                    </div>
                    <div class="detail-info-item">
                        <span class="detail-info-label"><i class="fa-solid fa-user-shield"></i> Zodpovědná osoba</span>
                        <strong id="detail-zodpovedna">—</strong>
                    </div>
                    <div class="detail-info-item">
                        <span class="detail-info-label"><i class="fa-solid fa-circle-check"></i> Status</span>
                        <span id="detail-status">—</span>
                    </div>
                    <div class="detail-info-item">
                        <span class="detail-info-label"><i class="fa-regular fa-calendar-check"></i> Poslední kontrola</span>
                        <strong id="detail-posledni">—</strong>
                    </div>
                    <div class="detail-info-item">
                        <span class="detail-info-label"><i class="fa-regular fa-calendar-days"></i> Další kontrola</span>
                        <strong id="detail-dalsi">—</strong>
                    </div>
                </div>

                <ul class="nav nav-pills detail-tabs mb-4" id="detailLekarnickyTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="detail-prehled-tab" data-bs-toggle="tab" data-bs-target="#detail-prehled-pane" type="button" role="tab" aria-controls="detail-prehled-pane" aria-selected="true">
                        <i class="fa-solid fa-house-medical me-2"></i>Přehled
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="detail-material-tab" data-bs-toggle="tab" data-bs-target="#detail-material-pane" type="button" role="tab" aria-controls="detail-material-pane" aria-selected="false">
                        <i class="fa-solid fa-boxes-stacked me-2"></i>Materiál
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="detail-urazy-tab" data-bs-toggle="tab" data-bs-target="#detail-urazy-pane" type="button" role="tab" aria-controls="detail-urazy-pane" aria-selected="false">
                        <i class="fa-solid fa-file-medical me-2"></i>Úrazy
                    </button>
                </li>
                </ul>

                <div class="tab-content" id="detailLekarnickyTabContent">
                <div class="tab-pane fade show active" id="detail-prehled-pane" role="tabpanel" aria-labelledby="detail-prehled-tab" tabindex="0">
                    <div class="detail-overview-grid mb-4">
                        <div class="detail-overview-card">
                            <span>Materiál</span>
                            <strong id="detail-material-count">0</strong>
                        </div>
                        <div class="detail-overview-card warning">
                            <span>Expiruje</span>
                            <strong id="detail-expiring-count">0</strong>
                        </div>
                        <div class="detail-overview-card danger">
                            <span>Nízký stav</span>
                            <strong id="detail-low-count">0</strong>
                        </div>
                        <div class="detail-overview-card info">
                            <span>Úrazy</span>
                            <strong id="detail-urazy-count">0</strong>
                        </div>
                    </div>

                    <div class="detail-popis">
                        <span class="detail-info-label"><i class="fa-regular fa-note-sticky"></i> Popis</span>
                        <p id="detail-popis" class="mb-0">—</p>
                    </div>
                </div>

                <div class="tab-pane fade" id="detail-material-pane" role="tabpanel" aria-labelledby="detail-material-tab" tabindex="0">
                    <div class="detail-table-section">
                        <div class="table-responsive rounded-3 overflow-hidden">
                            <table id="detailMaterialTable" class="table table-hover mb-0 w-100">
                                <thead class="table-dark-glass">
                                <tr>
                                    <th>Název</th>
                                    <th>Typ</th>
                                    <th class="text-center">Stav</th>
                                    <th class="text-center">Min / Max</th>
                                    <th>Expirace</th>
                                    <th class="text-center">Status</th>
                                </tr>
                                </thead>
                                <tbody id="detail-material-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="detail-urazy-pane" role="tabpanel" aria-labelledby="detail-urazy-tab" tabindex="0">
                    <div class="detail-table-section">
                        <div class="table-responsive rounded-3 overflow-hidden">
                            <table id="detailUrazyTable" class="table table-hover mb-0 w-100">
                                <thead class="table-dark-glass">
                                <tr>
                                    <th>Datum</th>
                                    <th>Zaměstnanec</th>
                                    <th>Místo</th>
                                    <th>Závažnost</th>
                                </tr>
                                </thead>
                                <tbody id="detail-urazy-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
            </div>
        </div>
    </div>
</div>
