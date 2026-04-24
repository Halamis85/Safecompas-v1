@extends('layouts.app')

@section('body-class', 'karta-zamestnance')



@section('content')
<div class="container ">
    <h1 class="text-center p-4 mb-5 mt-4">Karta zaměstnance</h1>
    <form id="vstup-jmeno">
        <div class="form-group autocomplete-container">
            <label for="zamestnanec" class="form-label visually-hidden">Vyhledat zaměstnance</label>
            <input type="text" class="form-control" id="zamestnanec" name="zamestnanec" autocomplete="off"
                   placeholder="Pro vyhledání karty zaměstnance zadejte jméno nebo příjmení" required>
            <div id="zamestnanec-list" class="list-group d-none"></div>
        </div>
    </form>

    <button id="closeB" class="btn btn-danger mb-3 d-none float-end">Zavřít</button>
    <div id="selected-employee" class="mb-3 text-center d-none"></div>

    <div id="table-container" class="m-bg-tran p-4 mb-5">
        <div class=""></div>
    </div>
</div>
@endsection
