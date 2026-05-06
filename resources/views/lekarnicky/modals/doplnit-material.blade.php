{{-- Modal pro doplnění již existujícího materiálu --}}
<div class="modal fade" id="doplnitMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-modal border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="text-uppercase small text-white-50 fw-semibold mb-1">Doplnění zásob</div>
                    <h5 class="modal-title fw-bold fs-4">
                        <i class="fa-solid fa-box-open me-2 text-primary"></i>Doplnit existující materiál
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <form id="doplnit-material-form">
                <input type="hidden" name="material_id" id="doplnit-material-id">
                <input type="hidden" name="objednavka_id" id="doplnit-objednavka-id">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lékárnička</label>
                            <select class="form-select glass-input" id="doplnit-lekarnicky-select">
                                <option value="">Všechny lékárničky</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Předaná objednávka *</label>
                            <select class="form-select glass-input" id="doplnit-material-select" required>
                                <option value="">-- vyberte předanou objednávku --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Aktuální stav</label>
                            <input type="text" class="form-control glass-input" id="doplnit-current-stock" value="—" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Doplnit o *</label>
                            <input type="number" class="form-control glass-input" name="mnozstvi" id="doplnit-quantity" min="1" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nová expirace</label>
                            <input type="date" class="form-control glass-input" name="datum_expirace">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Poznámka</label>
                        <textarea class="form-control glass-input" name="poznamky" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check me-1"></i> Doplnit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
