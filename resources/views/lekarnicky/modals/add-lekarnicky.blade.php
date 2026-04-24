<!-- Modal pro přidání lékárničky -->
<div class="modal fade" id="addLekarnickModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Přidat novou lékárničku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="add-lekarnicky-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Název lékárničky *</label>
                        <input type="text" class="form-control" name="nazev" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Umístění *</label>
                        <input type="text" class="form-control" name="umisteni" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zodpovědná osoba *</label>
                        <input type="text" class="form-control" name="zodpovedna_osoba" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Popis</label>
                        <textarea class="form-control" name="popis" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Datum další kontroly</label>
                        <input type="date" class="form-control" name="dalsi_kontrola">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">Uložit</button>
                </div>
            </form>
        </div>
    </div>
</div>
