@extends('layouts.app')

@section('body-class', 'lekarnicke-modul')

@section('content')
    @php
        $perms = session('user.permissions', []);
        $isSuperAdmin = session('user.is_super_admin');
        $canCreateLekarnicky = $isSuperAdmin || in_array('lekarnicke.create', $perms);
        $canManageMaterial = $canGlobalManageMaterial ?? ($isSuperAdmin || in_array('lekarnicke.material', $perms));
        $canIssueMaterial = $isSuperAdmin || in_array('lekarnicke.urazy', $perms);
        $userAlias = session('user.alias') ?: trim((session('user.firstname') ?? '') . ' ' . (session('user.lastname') ?? ''));
        $userAlias = $userAlias !== '' ? $userAlias : session('user.username', 'uživateli');
    @endphp
    <div class="container-fluid"
         id="lekarnicky-app"
         data-page-mode="overview"
         data-can-manage-material="{{ $canManageMaterial ? 'true' : 'false' }}"
         data-can-issue-material="{{ $canIssueMaterial ? 'true' : 'false' }}">

        <!-- Moderní Hero sekce -->
        <div class="lekarnicky-hero-section mb-4" id="lekarnicky-hero-section">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="lekarnicky-hero-content">
                    <div class="lekarnicky-hero-kicker">
                        <i class="fa-solid fa-kit-medical"></i>
                        <span>Modul lékárniček</span>
                    </div>
                    <h1 class="lekarnicky-hero-title mb-0">
                        Vítej zpět, <span>{{ $userAlias }}</span>
                    </h1>
                    <p class="lekarnicky-hero-subtitle mb-0">
                        Přehled kontrol, expirací a stavu vybavení na jednom místě.
                    </p>
                </div>
                
                <div class="dashboard-grid mb-4" id="dashboard-stats" style="display: none; gap: 4rem; grid-template-columns: repeat(5, auto);">
                    <div class="card-circle-primary primary floating" data-stat-container="celkem">
                        <h2 data-stat="celkem">0</h2>
                        <h6 class="nadpis">Celkem</h6>
                    </div>
                    <div class="card-circle-primary floating animated-circle-green success" data-stat-container="aktivni">
                        <h2 data-stat="aktivni">0</h2>
                        <h6 class="nadpis">Aktivní</h6>
                    </div>
                    <div class="card-circle-primary floating animated-circle-orange warning" data-stat-container="expirujici">
                        <h2 data-stat="expirujici">0</h2>
                        <h6 class="nadpis">Expirace</h6>
                    </div>
                    <div class="card-circle-primary floating animated-circle-red danger" data-stat-container="nizky-stav">
                        <h2 data-stat="nizky-stav">0</h2>
                        <h6 class="nadpis">Nízký stav</h6>
                    </div>
                    <div class="card-circle-primary floating animated-circle-blue info" data-stat-container="kontrola">
                        <h2 data-stat="kontrola">0</h2>
                        <h6 class="nadpis">Kontrola</h6>
                    </div>
                </div>
            </div>
        </div>
            
        <!-- Rychlé navigace -->
        <div class="d-flex flex-column flex-md-row justify-content-center align-items-stretch gap-4 mx-auto" id="navigation-cards" style="display: none; max-width: 1200px;">
                <div class="navigation-card-premium navigation-card" data-section="plan">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <div>
                        <h4>Plán budovy</h4>
                        <p>Přehled lékárniček v objektu.</p>
                    </div>
                </div>
                <div class="navigation-card-premium navigation-card" data-section="urazy">
                    <i class="fa-solid fa-file-medical"></i>
                    <div>
                        <h4>Kniha úrazů</h4>
                        <p>Záznamy o pracovních úrazech.</p>
                    </div>
                </div>
                <div class="navigation-card-premium navigation-card" data-section="vykazy">
                    <i class="fa-solid fa-chart-pie"></i>
                    <div>
                        <h4>Statistiky</h4>
                        <p>Grafy a exporty.</p>
                    </div>
                </div>
        </div>
        
        <!-- Loading indikátor -->
        <div id="loading-indicator" class="lekarnicky-loading mb-5" role="status" aria-live="polite">
            <span class="visually-hidden">Načítám lékárničky...</span>
            <div class="row g-4">
                @for($i = 0; $i < 6; $i++)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="lk-loading-card">
                        <div class="lk-loading-header">
                            <div class="lk-loading-icon loading-skeleton-pulse"></div>
                            <div class="lk-loading-title">
                                <div class="lk-loading-line loading-skeleton-pulse"></div>
                                <div class="lk-loading-line loading-skeleton-pulse short"></div>
                            </div>
                        </div>
                        <div class="lk-loading-meta">
                            <div class="lk-loading-line loading-skeleton-pulse"></div>
                            <div class="lk-loading-line loading-skeleton-pulse medium"></div>
                        </div>
                        <div class="lk-loading-metrics">
                            <div class="lk-loading-dot loading-skeleton-pulse"></div>
                            <div class="lk-loading-dot loading-skeleton-pulse"></div>
                            <div class="lk-loading-dot loading-skeleton-pulse"></div>
                            <div class="lk-loading-dot loading-skeleton-pulse"></div>
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </div>
        <!-- Obsah sekcí -->
        <div id="content-sections">
            <!-- Přehled lékárniček  -->
            <div id="section-prehled">
                <div class="row g-4 pt-5 pb-5" id="lekarnicke-list">
                    <!-- Dynamicky plněno z JS -->
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
                        @if($canManageMaterial)
                            <a class="btn btn-primary shadow-sm" href="{{ route('lekarnicke.admin') }}">
                                <i class="fa-solid fa-screwdriver-wrench me-1"></i> Administrace materiálu
                            </a>
                        @endif
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
                            <div class="card bg-opacity-5 border-white border-opacity-10 rounded-4 h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-4 text-uppercase">Trend úrazů v čase</h6>
                                    <div style="height: 350px;">
                                        <canvas id="injuriesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card bg-opacity-5 border-white border-opacity-10 rounded-4 h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-4 text-uppercase">Stav materiálu</h6>
                                    <div style="height: 350px;">
                                        <canvas id="materialStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Spodní řada: Export a další graf -->
                        <div class="col-lg-4">
                            <div class="card bg-opacity-5 border-white border-opacity-10 rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-uppercase">Export reportů</h6>
                                    <div class="mb-3">
                                        <label class="form-label small">Od:</label>
                                        <input type="date" class="form-control" id="export-od">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small">Do:</label>
                                        <input type="date" class="form-control" id="export-do">
                                    </div>
                                    <button class="btn btn-primary w-100 shadow-sm" id="export-vykaz">
                                        <i class="fa-solid fa-download me-1"></i> Generovat PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card bg-opacity-5 border-white border-opacity-10 rounded-4 h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-4 text-uppercase">Kontroly lékárniček (posledních 6 měsíců)</h6>
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

    @include('lekarnicky.modals.doplnit-material')
    @include('lekarnicky.modals.vydej-material')
    @include('lekarnicky.modals.add-uraz')
    @include('lekarnicky.modals.detail-lekarnicky')
    @include('lekarnicky.modals.plan-budovy')
        </div>
    </div>
@endsection
