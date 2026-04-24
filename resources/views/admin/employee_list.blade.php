@extends('layouts.app')

@section('body-class', 'employee-list')

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
            <h1>Přehled zaměstnanců</h1>
        </div>
        <div class="col-4"></div>
</div>

<div class="container">
    <div class="card-body mt-1 d-flex justify-content-center ">
        <div id="zprava-pri" class="alert alert-success d-none text-center w-25 "><span id="zprava-err" class="alert alert-danger d-none"></span>
        </div>
    </div>


    <div class="m-bg-tran p-5 mb-5">
                <div class="bg-light p-2">
                    <table id="employees-table" class="table table-striped table-hover table-responsive">
                        <thead>
                        <tr>
                            <th class="text-center">Jméno</th>
                            <th class="text-center">Přímeni</th>
                            <th class="text-center">Site</th>
                            <th class="text-center">Akce</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
@endsection


