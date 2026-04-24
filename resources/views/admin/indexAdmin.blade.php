@extends('layouts.app')


@section('content')
<div class="container-fluid">
    <div class="row justify-content-center mb-4 mt-5">
        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <a href="/email_contact" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none text-black fs-8"><i class="fa-solid fa-paper-plane fa-2x
                text-black pb-2"></i>Nastavení emailu pro automatické objednávky</a>
        </div>

        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <a href="/users" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none fs-8"><i class="fa-solid fa-users-gear
                fa-2x pb-2"></i>Správa údajů pro přihlášení do aplikace</a>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <a href="/employee_list" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none fs-8">
                <i class="fa-solid fa-list-check fa-2x pb-2"></i>Přehled zaměstnanců</a>
        </div>

        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <a href="/admin_employee" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none fs-8">
                <i class="fa-solid fa-user-plus fa-2x pb-2"></i>Přidat nového zaměstnance</a>
        </div>

        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <a href="/user_aktivity" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none fs-8">
                <i class="fa-solid fa-eye fa-2x pb-2"></i>Přehled aktivit v modulu OOPP</a>
        </div>

        <div class="col-4 col-sm-12 col-md-8 col-lg-2 d-flex justify-content-center align-items-center mb-4">
            <a href="/admin/permissions" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none fs-8">
                <i class="fa-solid fa-eye fa-2x pb-2"></i>Správa oprávnění a přístupů</a>
        </div>

    </div>
</div>
@endsection
