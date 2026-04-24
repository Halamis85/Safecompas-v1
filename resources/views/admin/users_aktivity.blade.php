@extends('layouts.app')

@section('body-class', 'administrace')

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
            <h1>Seznam Aktivit</h1>
        </div>
        <div class="col-4"></div>
    </div>

    <div class="m-bg-tran p-5 mb-5">
        <div class="bg-light p-2">
            <table id="activitiesTable" class="table table-striped table-bordered ">
                <thead>
                <tr>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

