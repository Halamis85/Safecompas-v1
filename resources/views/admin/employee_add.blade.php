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
            <h1>Přidat nového zaměstnance</h1>
        </div>
        <div class="col-4"></div>
    </div>


    <div class="card p-2 col-8 mx-auto align-items-center mb-6">&nbsp;
        <div class="col-8">
            <form id="add-employee-form" class="form-control-user">
                <div class="form-group row">
                    <div class="col-6 mb-3">
                        <label for="jmeno"></label>
                        <input type="text" class="form-control shadow-sm" id="jmeno" name="jmeno"
                               placeholder="Křestní jméno">
                    </div>
                    <div class="col-6 ">
                        <label for="prijmeni"></label>
                        <input type="text" class="form-control shadow-sm" id="prijmeni" name="prijmeni"
                               placeholder="Příjmení">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-6 ">
                        <label for="role">
                            <select id="stredisko" name="stredisko" required
                                    class="form-control shadow-sm">
                                <option value="" selected disabled>Zvolte nákladové středisko</option>
                                <option value="P422">P422 Výroba a servis</option>
                                <option value="P440">P440</option>
                                <option value="P450">P450</option>
                                <option value="452">P452 Sklad</option>
                                <option value="P470">P470</option>
                                <option value="P615">P615</option>
                                <option value="P811">P811</option>
                                <option value="P815">P815</option>
                                <option value="P816">P816</option>
                                <option value="P817">P817</option>
                                <option value="P818">P818</option>
                                <option value="P898">P898</option>
                                <option value="P813">P813</option>
                                <option value="Q817">Q817</option>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-center">
                    <button type="submit" class="col-4 btn btn-primary mb-5 mt-2"> Přidat
                        zaměstnance </button>
                </div>
            </form>
            <div id="zprava-pri" class="text-center alert-success d-none mb-5"></div>
            <span id="zprava-err" class="text-center alert alert-danger d-none mb-5"></span>
        </div>
    </div>
@endsection




