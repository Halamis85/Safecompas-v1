@extends('layouts.app')

@section('body-class', 'administrace')

@section('content')
<div class="container-fluid">
    <div class="row align-items-center">
        <div class="col-4 p-5">
            <a href="/admin" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none text-black fs-8"><i
                    class="fa-solid fa-backward fa-5x
                text-success pb-2"></i>Zpět</a>

        </div>
        <div class="col-4 text-center">
            <h1>Správa e-mailových kontaktů</h1>
        </div>
        <div class="col-4 p-5 d-flex justify-content-end">
            <button id="add-email-form" class="card-circle-icons animated-circle-icons d-flex flex-column justify-content-center
            align-items-center text-center text-decoration-none fs-8"><i id="add-icons" class="fa-solid fa-plus fa-4x text-success"></i>
                Přidat nový kontakt</button>
        </div>
    </div>
    <div id="contact-modal" class="d-none">
        <div class="container card col-3 mb-5 p-4">
            <H3 id="modal-title">Přidat emailový kontakt</H3>
            <input type="hidden" id="contact-id">
            <label for="contact-name">Název nebo jmeno kontaktu:</label><input type="text" class="input form-control"
                                                                               id="contact-name" required><br>
            <label for="contact-email">E-mail:</label><input class="input form-control" type="email" id="contact-email"
                                                             required><br>
            <label for="contact-type">Kam bude email odesílán ? </label><select class="form-select" id="contact-type">
                <option value="supplier">Dodavatel</option>
                <option value="Zákazník">Zákazníci</option>
                <option value="Uživatel">Uživatel</option></select> <br>
            <label for="contact-active">Aktivní:</label><input class="checkbox" type="checkbox" id="contact-active" checked><br>
            <button id="save-contact-btn" class="btn bg-light p-0">Uložit</button>
        </div>
    </div>
    <div class="container">
        <div class="card shadow-lg my-2">
            <div class="card-body p-0">
                <div class="m-bg-tran">
                    <table id="contacts-table" class="table table-bordered table-hover text-center p-4">
                        <thead class="thead-dark text-center">
                        <tr>
                            <th>Název nebo jméno kontaktu</th>
                            <th>E-mail</th>
                            <th>Odesílání</th>
                            <th>Aktivní</th>
                            <th>Akce</th>
                        </tr>
                        </thead>
                        <tbody id="user-list">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
