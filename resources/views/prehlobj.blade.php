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

<div class="container mb-3">
    <div id="notification-container" class="fixed-top" style="z-index: 2050;"></div>
    <div class="m-bg-tran p-5">
        <div class="bg-light p-2">
            <table id="activitiesTable" class="table bg-light table-striped table-hover">
                <thead>
                <th class="text-center">Objednáno</th>
                <th class="text-center">Zaměstnanec</th>
                <th class="text-center">Produkt</th>
                <th class="text-center">Velikost</th>
                <th class="text-center">Obrázek</th>
                <th class="text-center">Status</th>
                <th class="text-center">Akce</th>
                </thead>
                <tbody id="orders-list">
                <!-- Data se dynamicky doplňují -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="signatureModal" class="modal fade" tabindex="-1" aria-labelledby="signatureModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title text-center" id="signatureModalLabel">Podpis pro převzetí</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>
            <div class="modal-body">
                <canvas id="signatureCanvas" width="400" height="200" style="border:1px solid #ccc;"></canvas>
                <br>
                <button id="clearSignature" class="btn btn-secondary me-2">Vymazat</button>
            </div>
            <div class="modal-footer align-items-center justify-content-center">
                <button type="button" class="btn btn-secondary me-4" data-bs-dismiss="modal">Zavřít</button>
                <button type="button" id="confirmSignature" class="btn btn-primary">Potvrdit</button>
            </div>
        </div>
    </div>
</div>
@endsection
