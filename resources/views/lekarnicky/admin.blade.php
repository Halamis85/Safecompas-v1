@extends('layouts.app')

@section('body-class', 'lekarnicke-modul lekarnicke-admin-page')

@section('content')
    @php
        $perms = session('user.permissions', []);
        $isSuperAdmin = session('user.is_super_admin');
        $canCreateLekarnicky = $isSuperAdmin || in_array('lekarnicke.create', $perms);
        $canManageMaterial = $canGlobalManageMaterial ?? ($isSuperAdmin || in_array('lekarnicke.material', $perms));
    @endphp

    <div class="container-fluid"
         id="lekarnicky-app"
         data-page-mode="admin"
         data-can-manage-material="{{ $canManageMaterial ? 'true' : 'false' }}"
         data-can-issue-material="false">
        <div id="loading-indicator" class="text-center mt-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Načítání...</span>
            </div>
        </div>

        <section class="lekarnicky-admin-strip mt-4 mb-4">
            <div>
                <span class="admin-kicker">Administrace lékárniček</span>
                <strong>Přidávání lékárniček a nových materiálových položek</strong>
            </div>
            <div class="lekarnicky-toolbar-actions">
                <a class="btn btn-outline-primary" href="{{ route('lekarnicke.index') }}">
                    <i class="fa-solid fa-arrow-left me-2"></i>Zpět na přehled
                </a>
            </div>
        </section>

        <div class="row g-4 mb-4">
            @if($canCreateLekarnicky)
                <div class="col-lg-6">
                    <div class="lekarnicky-admin-card">
                        <div class="admin-card-icon"><i class="fa-solid fa-kit-medical"></i></div>
                        <div>
                            <h2>Přidat lékárničku</h2>
                            <p>Vytvoří novou lékárničku a přiřadí zodpovědnou osobu.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLekarnickModal">
                                <i class="fa-solid fa-plus me-2"></i>Přidat lékárničku
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            @if($canManageMaterial)
                <div class="col-lg-6">
                    <div class="lekarnicky-admin-card">
                        <div class="admin-card-icon"><i class="fa-solid fa-box-medical"></i></div>
                        <div>
                            <h2>Přidat nový materiál</h2>
                            <p>Slouží pro založení nové položky, kterou pak lze v přehledu pouze doplňovat nebo vydávat.</p>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                                <i class="fa-solid fa-box-open me-2"></i>Přidat materiál
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if($canManageMaterial)
            <div class="lekarnicky-board mb-4" id="lekarnicky-orders-board">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 class="mb-1">Objednávky materiálu</h2>
                        <p class="text-muted mb-0">Položky objednané z přehledu lékárniček a z výdeje materiálu.</p>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="show-vydane-orders" role="switch">
                            <label class="form-check-label text-muted" for="show-vydane-orders">Zobrazit doplněné</label>
                        </div>
                        <button type="button" class="btn btn-outline-primary" data-action="refresh-material-orders">
                            <i class="fa-solid fa-rotate me-2"></i>Obnovit
                        </button>
                    </div>
                </div>
                <div class="table-responsive rounded-3 overflow-hidden">
                    <table class="table table-hover mb-0" id="lekarnickyMaterialOrdersTable">
                        <thead class="table-dark-glass">
                        <tr>
                            <th>Materiál</th>
                            <th>Lékárnička</th>
                            <th class="text-center">Množství</th>
                            <th>Důvod</th>
                            <th class="text-center">Status</th>
                            <th>Objednáno</th>
                            <th class="text-end px-4">Akce</th>
                        </tr>
                        </thead>
                        <tbody id="lekarnicky-material-orders-tbody">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Načítání objednávek...</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="lekarnicky-board mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="mb-1">Existující lékárničky</h2>
                    <p class="text-muted mb-0">Rychlá kontrola, do kterých lékárniček už lze zakládat nové položky materiálu.</p>
                </div>
            </div>
            <div class="row g-3" id="lekarnicke-list"></div>
        </div>

        @include('lekarnicky.modals.add-lekarnicky')
        @include('lekarnicky.modals.add-material')
        @include('lekarnicky.modals.detail-lekarnicky')
    </div>
@endsection
