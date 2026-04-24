@extends('layouts.app')

@section('body-class', 'objednavka')



@section('content')
<div id="content-wrapper" class="container-fluid py-5">
    <div class="col-8 nadpis-new-oopp mx-auto text-center">
        <h1 class="p-2">Nový požadavek na OOPP</h1>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 mt-5">
            <form id="objednavka-form" novalidate class="row g-3 shadow-lg rounded p-4">
                <h5 class="mb-3">Vyber zaměstnance</h5>
                <div class="col-12">
                    <label for="zamestnanec" class="mb-1 form-label"> Zaměstnanec: </label>
                    <div class="mb-3 autocomplete-container">
                        <input type="text" class="form-control" id="zamestnanec" name="zamestnanec"
                               autocomplete="off" placeholder="Zadejte jméno nebo příjmení" required>
                        <div id="zamestnanec-list" class="list-group d-none"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="datum" class="form-label ">Dnešní datum</label>
                    <input type="date" class="form-control-plaintext text-center fs-6 fw-bold  " id="datum"
                           name="datum" readonly>
                </div>
                <div class="col-md-6">
                    <label for="stredisko" class="form-label">Nakládalové středisko:</label>
                    <input type="text" class="form-control-plaintext text-center fs-6 fw-bold " id="stredisko"
                           name="stredisko" readonly>
                </div>
                <hr class="my-4">
                <h5 class="mb-3">Výběr OOPP</h5>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="druh">Druh OOPP:</label>
                        <select id="druh" name="druh" class="form-control shadow" required>
                            <option value="">Vyberte druh OOPP</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="produkt">Produkt:</label>
                        <select id="produkt" name="produkt" class="form-control shadow" required>
                            <option value="">Nejprve vyberte druh</option>
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="mb-4">
                        <label for="velikost">Velikost:</label>
                        <select id="velikost" name="velikost" class="form-control shadow" required>
                            <option value="">Nejprve vyberte produkt</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary p-3 col-md-6 fs-5 shadow-lg">
                        <i class="fa-solid fa-cart-shopping pe-3"></i> Objednat OOPP
                    </button>
                </div>
            </form>
        </div>

        <div class="col-5 d-flex justify-content-center align-items-center">
            <div class="card-circle-new-oopp d-flex flex-column justify-content-center align-items-center text-center shadow">
                <div id="produkt-obrazek"></div>
                <div id="last-info" class="text-center">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center col-xl-12 d-flex flex-column justify-content-center mt-5">
    <div id="good-message" class="alert alert-success d-none mt-3 fs-6 fw-bold " role="alert"></div>
</div>
@endsection

