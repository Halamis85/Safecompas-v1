
@extends('layouts.app')

@section('body-class', 'permissions-admin')

@section('content')
    <div class="container-fluid" id="permissions-app">
        <h2>Správa oprávnění a přístupů</h2>

        <!-- Loading -->
        <div id="loading" class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Načítání...</span>
            </div>
        </div>

        <!-- Navigační tlačítka -->
        <div class="btn-group mb-4" role="group" id="navigation-buttons" style="display: none;">
            <button type="button" class="btn btn-primary section-btn active" data-section="roles">
                <i class="fa-solid fa-users-gear me-1"></i> Role
            </button>
            <button type="button" class="btn btn-outline-primary section-btn" data-section="users">
                <i class="fa-solid fa-users me-1"></i> Uživatelé
            </button>
            <button type="button" class="btn btn-outline-primary section-btn" data-section="permissions">
                <i class="fa-solid fa-shield-halved me-1"></i> Oprávnění
            </button>
        </div>

        <!-- Sekce Role -->
        <div id="section-roles" class="content-section">
            <div class="row">
                <div class="col-md-6">
                    <h4>Seznam rolí</h4>
                    <div id="roles-list" class="list-group">
                        <!-- Dynamicky generováno -->
                    </div>
                </div>
                <div class="col-md-6">
                    <h4 id="role-form-title">Nová role</h4>
                    <form id="role-form">
                        <div class="mb-3">
                            <label class="form-label">Název role *</label>
                            <input type="text" class="form-control" name="name" required>
                            <div class="form-text">Interní název (např. super_admin)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Zobrazovaný název *</label>
                            <input type="text" class="form-control" name="display_name" required>
                            <div class="form-text">Název zobrazovaný uživatelům</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Popis</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>

                        <h5>Oprávnění</h5>
                        <div id="permissions-checkboxes" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <!-- Dynamicky generováno -->
                        </div>

                        <h5 class="mt-4">Notifikace</h5>
                        <div class="border rounded p-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="notify-oopp-order">
                                <label class="form-check-label" for="notify-oopp-order">
                                    <strong>OOPP — nová objednávka</strong><br>
                                    <small class="text-muted">Uživatelé s touto rolí dostanou e-mail při každé nové objednávce OOPP.</small>
                                </label>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fa-solid fa-save me-1"></i> Uložit
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancel-role">
                                <i class="fa-solid fa-times me-1"></i> Zrušit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Sekce Oprávnění -->
        <div id="section-users" class="content-section" style="display: none;">
            <h4>Správa uživatelských rolí</h4>
            <div class="table-responsive">
                <table class="table table-hover" id="users-table">
                    <thead>
                    <tr>
                        <th>Uživatel</th>
                        <th>Aktuální role</th>
                        <th>Akce</th>
                    </tr>
                    </thead>
                    <tbody id="users-tbody">
                    <!-- Dynamicky generováno -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sekce Oprávnění -->
        <div id="section-permissions" class="content-section" style="display: none;">
            <h4>Přehled oprávnění podle modulů</h4>
            <div id="permissions-overview">
                <!-- Dynamicky generováno -->
            </div>
        </div>
    </div>
    <!-- Modal: editace rolí uživatele -->
<div class="modal fade" id="editUserRolesModal" tabindex="-1"
     aria-labelledby="editUserRolesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserRolesModalLabel">
                    <i class="fa-solid fa-users-gear me-2"></i>
                    Editace rolí uživatele
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>

            <div class="modal-body">
                {{-- Loading stav --}}
                <div id="edit-user-roles-loading" class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Načítání…</span>
                    </div>
                </div>

                {{-- Obsah - skrytý dokud se nenačtou data --}}
                <div id="edit-user-roles-content" class="d-none">
                    <div class="mb-3">
                        <strong>Uživatel:</strong>
                        <span id="edit-user-roles-username" class="text-muted ms-1"></span>
                    </div>

                    <p class="text-muted small mb-3">
                        Zaškrtněte role, které má uživatel mít. Změny se uloží po kliknutí
                        na <strong>Uložit změny</strong>.
                    </p>

                    <form id="edit-user-roles-form" novalidate>
                        <input type="hidden" id="edit-user-roles-user-id" value="">
                        <div id="edit-user-roles-checkboxes"
                             class="border rounded p-3"
                             style="max-height: 400px; overflow-y: auto;">
                            {{-- Dynamicky generované checkboxy rolí --}}
                        </div>
                    </form>
                </div>

                {{-- Chybový stav --}}
                <div id="edit-user-roles-error" class="alert alert-danger d-none mt-3" role="alert">
                    {{-- text chyby --}}
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i> Zrušit
                </button>
                <button type="button" class="btn btn-success" id="edit-user-roles-save" disabled>
                    <i class="fa-solid fa-save me-1"></i> Uložit změny
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
