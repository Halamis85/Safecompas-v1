@extends('layouts.app')

@section('body-class', 'prehled')


@section('content')
<h1 class="text-center mt-5">Přehled objednávek</h1>
<div id="content-wrapper" class="container-fluid py-5">
    <div id="content">
        <div class="text-center col-xl-12 d-flex flex-column justify-content-center ">
            <div id="notification-container" class="fixed-top" style="z-index: 2050;"></div>
            <div id="good-message" class="alert alert-success d-none mt-3 fs-6 fw-bold " role="alert"></div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="card glass-strong border-0 shadow-lg">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-primary mb-0">Seznam objednávek</h3>
                <a href="{{ route('export.objednavky') }}"
                   class="btn btn-outline-success btn-sm"
                   title="Stáhnout tabulku jako XLSX">
                    <i class="fa-solid fa-file-excel me-1"></i> Export do Excelu
                </a>
            </div>

            <div class="table-responsive">
                <table id="activitiesTable" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">Objednáno</th>
                            <th>Zaměstnanec</th>
                            <th>Produkt</th>
                            <th class="text-center">Velikost</th>
                            <th class="text-center">Počet ks</th>
                            <th class="text-center">Obrázek</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Akce</th>
                        </tr>
                    </thead>
                    <tbody id="orders-list">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="signatureModal" class="modal fade" tabindex="-1" aria-labelledby="signatureModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal border-0 shadow-xl">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title w-100 text-center fw-bold" id="signatureModalLabel">
                    <i class="fa-solid fa-pen-nib me-2"></i>Potvrzení převzetí
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <p class="text-secondary small mb-3">Prosím, podepište se do pole níže pro potvrzení převzetí OOPP.</p>
                
                <div class="signature-wrapper p-1 bg-white rounded-3 shadow-sm mb-3">
                    <canvas id="signatureCanvas" width="400" height="200" class="w-100 h-auto" style="cursor: crosshair;"></canvas>
                </div>
                
                <div class="d-flex justify-content-center gap-2">
                    <button id="clearSignature" class="btn btn-outline-danger btn-sm px-3">
                        <i class="fa-solid fa-eraser me-1"></i> Vymazat
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center pb-4">
                <button type="button" class="btn btn-outline-light me-3 px-4" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Zrušit
                </button>
                <button type="button" id="confirmSignature" class="btn btn-primary px-5 shadow" disabled>
                    <i class="fa-solid fa-check me-1"></i> Potvrdit převzetí
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
