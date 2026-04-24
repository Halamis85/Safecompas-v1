@extends('layouts.app')

@section('body-class', 'email-contacts')

@section('content')
<div class="container-fluid">
    <div class="row align-items-center">
        <div class="col-4 p-5">
            <a href="/admin" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none text-black fs-8">
                <i class="fa-solid fa-backward fa-5x text-success pb-2"></i>Zpět
            </a>
        </div>
        <div class="col-4 text-center">
            <h1>Správa e-mailových kontaktů</h1>
        </div>
        <div class="col-4 p-5 d-flex justify-content-end">
            <button id="add-contact-btn" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none fs-8">
                <i class="fa-solid fa-plus fa-4x text-success"></i>
                Přidat nový kontakt
            </button>
        </div>
    </div>

    <div id="contact-form-wrapper" class="d-none mb-4">
        <div class="container card col-md-6 col-lg-4 mx-auto p-4">
            <h3 id="contact-modal-title">Přidat emailový kontakt</h3>

            <form id="contact-form">
                <input type="hidden" id="contact-id" name="contact_id">

                <div class="mb-3">
                    <label for="contact-name" class="form-label">Název nebo jméno kontaktu:</label>
                    <input type="text" class="form-control" id="contact-name" name="name" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label for="contact-email" class="form-label">E-mail:</label>
                    <input type="email" class="form-control" id="contact-email" name="email" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label for="contact-type" class="form-label">Kam bude email odesílán:</label>
                    <select class="form-select" id="contact-type" name="type" required>
                        <option value="supplier">Dodavatel</option>
                        <option value="customer">Zákazník</option>
                        <option value="user">Uživatel</option>
                    </select>
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" type="checkbox" id="contact-active" name="is_active" checked>
                    <label class="form-check-label" for="contact-active">Aktivní</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="save-contact-btn">Uložit</button>
                    <button type="button" class="btn btn-secondary" id="cancel-contact-btn">Zrušit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="contacts-message" class="alert d-none text-center mx-auto" style="max-width: 500px;"></div>

    <div class="container">
        <div class="card shadow-lg my-2">
            <div class="card-body p-0">
                <div class="m-bg-tran">
                    <table id="contacts-table" class="table table-bordered table-hover text-center p-4">
                        <thead class="thead-dark text-center">
                        <tr>
                            <th>Název nebo jméno kontaktu</th>
                            <th>E-mail</th>
                            <th>Typ</th>
                            <th>Aktivní</th>
                            <th>Akce</th>
                        </tr>
                        </thead>
                        <tbody id="contacts-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection