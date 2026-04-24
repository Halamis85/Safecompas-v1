@extends('layouts.app')

@section('body-class', 'dashboard-home')

@section('content')
<div class="container-fluid" xmlns="http://www.w3.org/1999/html">
    <div class="row mb-5"></div>
    <h4 class="text-center pt-4 d-flex flex-wrap align-items-center justify-content-center text-muted">
        Ahoj {{session('user.alias')}} <span class="text-name ms-1 me-1"> </span>  dnes je
        <span id="display-date-full" class="ms-1 me-1 text-muted"></span> <span id="holiday-found-section" class="d-none"> svátek má<span id="holiday-name" class="ms-1"></span>.
<span id="public-holiday-warning" class="text-warning small ms-2 d-none">
            <i class="fas fa-exclamation-triangle"></i> Dnes je státní svátek!
        </span>
    </span>

        <span id="no-holiday-section" class="d-none"> a dnes nikdo svátek nemá.
    </span>

        <span id="loading-or-error-message" class="ms-1 text-muted">Načítání informací...</span>
    </h4>

    <div class="row justify-content-center mb-4 mt-5">

        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <div class="card-circle-weather d-flex flex-column justify-content-center align-items-center text-center text-white">
                <div id="weather-info" class="text-center fs-5">
                    <p class="text-muted">Načítám data o počasí...</p>
                </div>
            </div>
        </div>


        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <div class="card-circle animated-circle-red bg-circle-shadow-red d-flex flex-column justify-content-center align-items-center text-center text-white">
                <canvas id="clock" width="243" height="245"><</canvas>
            </div>
        </div>


        <!-- Kontejner součet částky  -->
        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <div class="card-circle animated-circle-blue bg-circle-shadow-blue d-flex flex-column justify-content-center align-items-center text-center text-white">
                <div class="nadpis mb-2">Vynaložená částka <br> za rok <span id="rok-zobrazeni"></span> </div>
                <h6 id="celkova-castka">Kč</h6>
                <canvas id="grafVydaje" style=" width:150px; height: 90px;"></canvas>
            </div>
        </div>


        <!-- objednané objednávky počet panel-->
        <div class="col-4 col-sm-5 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <div class="card-circle animated-circle-orange bg-circle-shadow-orange d-flex flex-column justify-content-center align-items-center text-center text-white">
                <h6 class="mb-2 overflow px-4">Objednávky u dodavatele</h6>
                <h2 class="mb-xl-4 mb-sm-2" id="obje-count">4</h2>
                <i class="fas fa-truck-arrow-right fa-2x mt-0"></i>
            </div>
        </div>
        <!-- Čekající objednávka počet-->
        <div class="col-4 col-sm-5 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <div class="card-circle animated-circle-green bg-circle-shadow-green d-flex flex-column justify-content-center align-items-center text-center text-white">
                <h6 class="mb-2 overflow px-5">Čekající požadavky</h6>
                <h2 class="mb-xl-4 mb-sm-1" id="ceka-coumt">42</h2>
                <i class="fa-solid fa-list-check fa-2x mt-0"></i>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">

        <!-- Čekající objednávky -->
        <div class="col-md-10 col-lg-8 mt-3 mb-5">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center text-center">
                <h6 class="text-center fs-4">Čekajicí požadavky</h6>
            </div>
            <div class="card">
                <div id="orders-container" class="order-item scrollable-orders"></div>
            </div>
        </div>


        <!-- Graf koláč -->
        <div class="col-md-8 col-lg-6 mb-5">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center text-center">
                <h6 class="text-center fs-4">Statistika vydaných produktů za rok </h6>
            </div>
            <div class="card p-5">
                <div class="chart-pie">
                    <canvas id="statistikaGraf"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


