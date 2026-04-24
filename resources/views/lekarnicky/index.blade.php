<!-- resources/views/lekarnicky/index.blade.php - OPRAVENO -->
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
                     data-section="prehled" style="cursor: pointer;">
                    <i class="fa-solid fa-list fa-2x pb-2"></i>
                    <span>Přehled lékárniček</span>
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
            <!-- Přehled lékárniček -->
            <div id="section-prehled" class="content-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Přehled lékárniček</h3>
                    <!-- Dočasně zobrazit tlačítko všem -->
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLekarnickModal">
                        <i class="fa-solid fa-plus"></i> Přidat lékárničku
                    </button>
                </div>

                <div class="row" id="lekarnicke-list">
                    <!-- Dynamicky generovaný seznam lékárniček -->
                </div>
            </div>

            <!-- Správa materiálu -->
            <div id="section-material" class="content-section" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Správa materiálu</h3>
                    <div>
                        <select class="form-select d-inline-block w-auto me-2" id="material-lekarnicky-filter">
                            <option value="">Vyberte lékárničku</option>
                        </select>
                        <!-- Dočasně zobrazit tlačítko všem -->
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                            <i class="fa-solid fa-plus"></i> Přidat materiál
                        </button>
                    </div>
                </div>

                <div class="m-bg-tran p-3">
                    <div class="bg-light p-2">
                        <table id="materialTable" class="table bg-light table-striped table-hover">
                            <thead>
                            <tr>
                                <th>Lékárnička</th>
                                <th>Materiál</th>
                                <th>Typ</th>
                                <th>Aktuální stav</th>
                                <th>Min. stav</th>
                                <th>Expirace</th>
                                <th>Status</th>
                                <th>Akce</th>
                            </tr>
                            </thead>
                            <tbody id="material-tbody">
                            <!-- Dynamicky generovaná data -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Záznamy úrazů -->
            <div id="section-urazy" class="content-section" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Záznamy úrazů</h3>
                    <!-- Dočasně zobrazit tlačítko všem -->
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUrazModal">
                        <i class="fa-solid fa-plus"></i> Zaznamenat úraz
                    </button>
                </div>

                <div class="m-bg-tran p-3">
                    <div class="bg-light p-2">
                        <table id="urazyTable" class="table bg-light table-striped table-hover">
                            <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Zaměstnanec</th>
                                <th>Místo úrazu</th>
                                <th>Závažnost</th>
                                <th>Lékárnička</th>
                                <th>Akce</th>
                            </tr>
                            </thead>
                            <tbody id="urazy-tbody">
                            <!-- Dynamicky generovaná data -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Výkazy a statistiky -->
            <div id="section-vykazy" class="content-section" style="display: none;">
                <h3>Výkazy a statistiky</h3>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Export dat</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Období od:</label>
                                    <input type="date" class="form-control" id="export-od">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Období do:</label>
                                    <input type="date" class="form-control" id="export-do">
                                </div>
                                <!-- Dočasně zobrazit tlačítko všem -->
                                <button class="btn btn-success" id="export-vykaz">
                                    <i class="fa-solid fa-download"></i> Exportovat výkaz
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Rychlé statistiky</h5>
                            </div>
                            <div class="card-body" id="quick-stats">
                                <!-- Dynamicky generované statistiky -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prázdné modaly pro začátek -->
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                        <button type="submit" class="btn btn-primary">Uložit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('lekarnicky.modals.add-material')
    @include('lekarnicky.modals.add-uraz')
    @include('lekarnicky.modals.detail-lekarnicky')
@endsection
