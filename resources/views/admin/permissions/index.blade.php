
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
@endsection
