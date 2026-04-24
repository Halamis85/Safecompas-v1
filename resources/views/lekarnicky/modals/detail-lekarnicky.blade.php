{{-- Modal s detailem lékárničky --}}
<div class="modal fade" id="detailLekarnickyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detail-lekarnicky-title">Detail lékárničky</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Umístění:</strong> <span id="detail-umisteni">—</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Zodpovědná osoba:</strong> <span id="detail-zodpovedna">—</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Status:</strong> <span id="detail-status">—</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Poslední kontrola:</strong> <span id="detail-posledni">—</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Další kontrola:</strong> <span id="detail-dalsi">—</span>
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Popis:</strong>
                    <p id="detail-popis" class="text-muted mb-0">—</p>
                </div>

                <hr>

                <h6 class="mt-3">Materiál</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <th>Název</th>
                            <th>Typ</th>
                            <th>Stav</th>
                            <th>Min / Max</th>
                            <th>Expirace</th>
                        </tr>
                        </thead>
                        <tbody id="detail-material-tbody">
                        <tr><td colspan="5" class="text-center text-muted">Žádný materiál</td></tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="mt-4">Úrazy evidované k této lékárničce</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Zaměstnanec</th>
                            <th>Místo</th>
                            <th>Závažnost</th>
                        </tr>
                        </thead>
                        <tbody id="detail-urazy-tbody">
                        <tr><td colspan="4" class="text-center text-muted">Žádný úraz</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
            </div>
        </div>
    </div>
</div>