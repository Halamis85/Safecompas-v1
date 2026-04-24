{{-- Modal pro přidání / editaci materiálu --}}
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="materialModalTitle">Přidat materiál</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <form id="material-form">
                <input type="hidden" name="material_id" id="material-id" value="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lékárnička *</label>
                            <select class="form-select" name="lekarnicky_id" id="material-lekarnicky-select" required>
                                <option value="">-- vyberte --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Typ materiálu *</label>
                            <select class="form-select" name="typ_materialu" required>
                                <option value="">-- vyberte --</option>
                                <option value="obvaz">Obvaz</option>
                                <option value="dezinfekce">Dezinfekce</option>
                                <option value="tablety">Tablety / léky</option>
                                <option value="naplast">Náplast</option>
                                <option value="rouska">Rouška / rukavice</option>
                                <option value="nastroje">Nástroje</option>
                                <option value="jine">Jiné</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Název materiálu *</label>
                        <input type="text" class="form-control" name="nazev_materialu" maxlength="255" required>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Aktuální stav *</label>
                            <input type="number" class="form-control" name="aktualni_pocet" min="0" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Minimum *</label>
                            <input type="number" class="form-control" name="minimalni_pocet" min="0" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Maximum *</label>
                            <input type="number" class="form-control" name="maximalni_pocet" min="1" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jednotka *</label>
                            <input type="text" class="form-control" name="jednotka" placeholder="ks, ml, g…" maxlength="50" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Datum expirace</label>
                            <input type="date" class="form-control" name="datum_expirace">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cena za jednotku (Kč)</label>
                            <input type="number" class="form-control" name="cena_za_jednotku" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dodavatel</label>
                        <input type="text" class="form-control" name="dodavatel" maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Poznámky</label>
                        <textarea class="form-control" name="poznamky" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary" id="material-submit-btn">Uložit</button>
                </div>
            </form>
        </div>
    </div>
</div>
