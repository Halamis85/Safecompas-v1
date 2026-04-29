@extends('layouts.app')

@section('body-class', 'lekarnicke-modul')

@section('content')
    <div class="container-fluid" id="lekarnicky-app">
        <h1 class="text-center mt-5">Správa lékárniček</h1>

        <!-- Loading indikátor -->
        <div id="loading-indicator" class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Načítání...</span>
            </div>
        </div>

        <!-- Dashboard karty -->
        <div class="row mb-4" id="dashboard-stats" style="display: none;">
            <div class="col-md-2 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fa-solid fa-kit-medical fa-2x mb-2"></i>
                        <h4 data-stat="celkem">0</h4>
                        <p class="mb-0">Celkem lékárniček</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fa-solid fa-check-circle fa-2x mb-2"></i>
                        <h4 data-stat="aktivni">0</h4>
                        <p class="mb-0">Aktivní</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fa-solid fa-exclamation-triangle fa-2x mb-2"></i>
                        <h4 data-stat="expirujici">0</h4>
                        <p class="mb-0">Expirující materiál</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fa-solid fa-arrow-down fa-2x mb-2"></i>
                        <h4 data-stat="nizky-stav">0</h4>
                        <p class="mb-0">Nízký stav</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fa-solid fa-calendar-check fa-2x mb-2"></i>
                        <h4 data-stat="kontrola">0</h4>
                        <p class="mb-0">Potřeba kontroly</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <i class="fa-solid fa-band-aid fa-2x mb-2"></i>
                        <h4 data-stat="urazy">0</h4>
                        <p class="mb-0">Úrazy tento měsíc</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigační karty -->
        <div class="row justify-content-center mb-4" id="navigation-cards" style="display: none;">
           <div class="col-md-3 mb-3">
                <div class="card-circle-icons animated-circle-icons d-flex flex-column
                        justify-content-center align-items-center text-center navigation-card"
                     data-section="plan" style="cursor: pointer;">
                    <i class="fa-solid fa-map-location-dot fa-2x pb-2"></i>
                    <span>Umístění lékárniček</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card-circle-icons animated-circle-icons d-flex flex-column
                        justify-content-center align-items-center text-center navigation-card"
                     data-section="material" style="cursor: pointer;">
                    <i class="fa-solid fa-boxes fa-2x pb-2"></i>
                    <span>Správa materiálu</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card-circle-icons animated-circle-icons d-flex flex-column
                        justify-content-center align-items-center text-center navigation-card"
                     data-section="urazy" style="cursor: pointer;">
                    <i class="fa-solid fa-file-medical fa-2x pb-2"></i>
                    <span>Záznamy úrazů</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card-circle-icons animated-circle-icons d-flex flex-column
                        justify-content-center align-items-center text-center navigation-card"
                     data-section="vykazy" style="cursor: pointer;">
                    <i class="fa-solid fa-chart-bar fa-2x pb-2"></i>
                    <span>Výkazy a statistiky</span>
                </div>
            </div>
        </div>

             <!-- Obsah sekcí -->
    <div id="content-sections">
        <!-- Přehled lékárniček  -->
            <div id="section-prehled">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Přehled lékárniček</h3>
                    @php
                        $perms = session('user.permissions', []);
                        $canCreateLekarnicky = session('user.is_super_admin')
                            || in_array('lekarnicke.create', $perms);
                    @endphp
                    @if($canCreateLekarnicky)
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLekarnickModal">
                            <i class="fa-solid fa-plus"></i> Přidat lékárničku
                        </button>
                    @endif
                </div>

                <div class="row" id="lekarnicke-list">
                    <!-- Dynamicky generovaný seznam lékárniček -->
                </div>
            </div>

    <!-- Prázdné modaly pro začátek -->
    <div class="modal fade" id="addLekarnickModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-modal border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Přidat novou lékárničku</h5>
                    <button type="button" class="btn-close " data-bs-dismiss="modal"></button>
                </div>
                <form id="add-lekarnicky-form">
                    <div class="modal-body p-4">
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
                            <select class="form-select" name="zodpovedna_osoba_user_id" id="lekarnicky-owner-select" required>
                                <option value="">-- vyberte uživatele --</option>
                            </select>
                            <small class="form-text text-muted">
                                Tento uživatel bude dostávat e-mailové notifikace o stavu lékárničky (expirace, nízký stav, kontroly).
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Popis</label>
                            <textarea class="form-control" name="popis" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                        <button type="submit" class="btn btn-primary">Uložit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal pro Seznam materiálu -->
    <div class="modal fade" id="materialModalList" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content glass-modal border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold fs-4"><i class="fa-solid fa-boxes-stacked me-2"></i> Správa materiálu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <select class="form-select d-inline-block w-auto glass-input" id="material-lekarnicky-filter">
                            <option value="">Všechny lékárničky</option>
                        </select>
                        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                            <i class="fa-solid fa-plus me-1"></i> Přidat materiál
                        </button>
                    </div>
                    <div class="table-responsive rounded-3 overflow-hidden">
                        <table id="materialTable" class="table table-hover mb-0">
                            <thead class="table-dark-glass">
                                <tr>
                                    <th>Lékárnička</th>
                                    <th>Materiál</th>
                                    <th>Typ</th>
                                    <th class="text-center">Stav</th>
                                    <th class="text-center">Minimálně</th>
                                    <th>Expirace</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end px-4">Akce</th>
                                </tr>
                            </thead>
                            <tbody id="material-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pro Záznamy úrazů -->
    <div class="modal fade" id="urazyModalList" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content glass-modal border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold fs-4"><i class="fa-solid fa-file-waveform me-2"></i> Záznamy úrazů</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-end mb-4">
                        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUrazModal">
                            <i class="fa-solid fa-plus me-1"></i> Zaznamenat úraz
                        </button>
                    </div>
                    <div class="table-responsive rounded-3 overflow-hidden">
                        <table id="urazyTable" class="table table-hover mb-0">
                            <thead class="table-dark-glass">
                                <tr>
                                    <th>Datum</th>
                                    <th>Zaměstnanec</th>
                                    <th>Místo úrazu</th>
                                    <th>Závažnost</th>
                                    <th>Lékárnička</th>
                                    <th class="text-end px-4">Akce</th>
                                </tr>
                            </thead>
                            <tbody id="urazy-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @include('lekarnicky.modals.add-material')
    <!-- Modal pro Výkazy a statistiky - FULLSCREEN ANALYTICS -->
    <div class="modal fade" id="vykazyModalList" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content glass-modal border-0">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold fs-3 text-white">
                        <i class="fa-solid fa-chart-pie me-2 text-primary"></i> Analytický přehled lékárniček
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="row g-4">
                        <!-- Horní řada: Rychlé karty -->
                        <div class="col-12">
                            <div class="row g-3" id="stats-mini-cards">
                                <!-- Dynamicky plněno z JS -->
                            </div>
                        </div>

                        <!-- Střední řada: Grafy -->
                        <div class="col-lg-8">
                            <div class="card bg-white bg-opacity-5 border-white border-opacity-10 rounded-4 h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-4 text-white-50 small text-uppercase">Trend úrazů v čase</h6>
                                    <div style="height: 350px;">
                                        <canvas id="injuriesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card bg-white bg-opacity-5 border-white border-opacity-10 rounded-4 h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-4 text-white-50 small text-uppercase">Stav materiálu</h6>
                                    <div style="height: 350px;">
                                        <canvas id="materialStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Spodní řada: Export a další graf -->
                        <div class="col-lg-4">
                            <div class="card bg-white bg-opacity-5 border-white border-opacity-10 rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-primary small text-uppercase">Export reportů</h6>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Od:</label>
                                        <input type="date" class="form-control glass-input" id="export-od">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Do:</label>
                                        <input type="date" class="form-control glass-input" id="export-do">
                                    </div>
                                    <button class="btn btn-primary w-100 shadow-sm" id="export-vykaz">
                                        <i class="fa-solid fa-download me-1"></i> Generovat PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card bg-white bg-opacity-5 border-white border-opacity-10 rounded-4 h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-4 text-white-50 small text-uppercase">Kontroly lékárniček (posledních 6 měsíců)</h6>
                                    <div style="height: 250px;">
                                        <canvas id="inspectionsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('lekarnicky.modals.add-material')
    @include('lekarnicky.modals.add-uraz')
    @include('lekarnicky.modals.detail-lekarnicky')
    @include('lekarnicky.modals.plan-budovy')
@endsection
