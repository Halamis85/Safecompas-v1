{{-- Modal pro záznam úrazu --}}
<div class="modal fade" id="addUrazModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Zaznamenat úraz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <form id="uraz-form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Zaměstnanec *</label>
                            <select class="form-select" name="zamestnanec_id" id="uraz-zamestnanec-select" required>
                                <option value="">-- vyberte --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lékárnička *</label>
                            <select class="form-select" name="lekarnicky_id" id="uraz-lekarnicky-select" required>
                                <option value="">-- vyberte --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Datum a čas úrazu *</label>
                            <input type="datetime-local" class="form-control" name="datum_cas_urazu" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Závažnost *</label>
                            <select class="form-select" name="zavaznost" required>
                                <option value="">-- vyberte --</option>
                                <option value="lehky">Lehký</option>
                                <option value="stredni">Střední</option>
                                <option value="tezky">Těžký</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Místo úrazu *</label>
                        <input type="text" class="form-control" name="misto_urazu" placeholder="např. Dílna 2, hala A" maxlength="255" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Popis úrazu *</label>
                        <textarea class="form-control" name="popis_urazu" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Poskytnuté ošetření *</label>
                        <textarea class="form-control" name="poskytnute_osetreni" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Osoba poskytující pomoc *</label>
                            <input type="text" class="form-control" name="osoba_poskytujici_pomoc" maxlength="255" required>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="prevezen_do_nemocnice" value="1" id="prevezen-check">
                                <label class="form-check-label" for="prevezen-check">Převezen do nemocnice</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Poznámky</label>
                        <textarea class="form-control" name="poznamky" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-danger">Zaznamenat úraz</button>
                </div>
            </form>
        </div>
    </div>
</div>
