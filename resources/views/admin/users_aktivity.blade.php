@extends('layouts.app')

@section('body-class', 'users-activity')

@section('content')
<div class="container-fluid">
    <div class="row align-items-center">
        <div class="col-4 p-5">
            <a href="/admin" class="card-circle-icons animated-circle-icons d-flex flex-column
                justify-content-center align-items-center text-center text-decoration-none text-black fs-8"><i
                    class="fa-solid fa-backward fa-4x
                text-success pb-2"></i>Zpět</a>
        </div>
        <div class="col-4 text-center">
            <h1>Seznam aktivit</h1>
        </div>
        <div class="col-4"></div>
    </div>

    <div class="m-bg-tran p-5 mb-5">
                <div class="d-flex justify-content-end mb-2">
                <a href="{{ route('export.aktivity') }}"
                    class="btn btn-outline-success btn-sm"
                    title="Stáhnout tabulku jako XLSX">
                    <i class="fa-solid fa-file-excel me-1"></i> Export do Excelu
                </a>
            </div>
        <div class="bg-light p-2">
            <table id="activitiesTable" class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th class="text-center">Datum a čas</th>
                    <th class="text-center">Uživatel</th>
                    <th class="text-center">Typ aktivity</th>
                    <th class="text-center">Detaily</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection