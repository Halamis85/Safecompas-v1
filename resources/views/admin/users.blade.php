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
            <h1>Vytvoření uživatelského účtu</h1>
        </div>
        <div class="col-4 p-5 d-flex justify-content-end">
            <button id="add-user-form" class="card-circle-icons animated-circle-icons d-flex flex-column justify-content-center
            align-items-center text-center text-decoration-none fs-8"><i id="add-icon" class="fa-solid fa-plus fa-4x text-success"></i>
                Přidat uživatelský účet</button>
        </div>
    </div>

    <div id="add-user-form-zobrazit" style="display: none">
        <div class="container">
            <div class="card o-hidden shadow-sm pt-3 mb-5">
                <div class="row">
                    <div class="col-lg-3"></div>
                    <div class="col-lg-5">
                        <div class="mb-3">
                            <form id="add-admin-form" class="form-control-user">
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-1">
                                        <label for="firstname"></label>
                                        <input type="text" class="form-control shadow-sm" id="firstname" name="firstname"
                                               placeholder="Křestní jméno">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="lastname"></label>
                                        <input type="text" class="form-control shadow-sm" id="lastname" name="lastname"
                                               placeholder="Příjmení">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-13">
                                        <label for="email"></label>
                                        <input type="email" class="form-control shadow-sm" id="email" name="email"
                                               placeholder="Emailová Adresa">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <label for="username"></label>
                                        <input type="text" class="form-control shadow-sm"
                                               id="username" name="username" placeholder="Uživatelské jmeno">
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="password" class="form-label"></label>
                                        <input type="password" class="form-control shadow-sm"
                                               id="password" name="password" placeholder="Heslo">
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="alias" class="form-label"></label>
                                        <input type="text" class="form-control shadow-sm"
                                               id="alias" name="alias" placeholder="Oslovení ">
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-6 ">
                                            <label for="role"></label>
                                            <select id="role" name="role" required class="form-control shadow-sm">
                                                <option value="" selected disabled>Vyberte uživatelskou roly</option>
                                                <option value="admin">Admin</option>
                                                <option value="user">User</option>
                                                <option value="editor">Editor</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block shadow mt-4 mb-4 d">
                                        <i class="fa-solid fa-plus me-2"></i>Vytvořit uživatelský účet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="zprava-pri" class="alert-success d-none mx-auto fs-5" role="alert"></div>
        <div id="zprava-err" class="alert-danger d-none mx-auto fs-5" role="alert"></div>
    </div>
</div>
<div class="container">
    <div class="card o-hidden border-0 shadow-lg my-2">
        <div class="card-body p-0">
            <div class="m-bg-tran">
                <div id="taber" class=" text-center">
                    <h3 class="text-light ">Seznam uživatelů</h3>
                    <table class="table table-bordered table-hover text-center p-4 ">
                        <thead class="thead-dark text-center">
                        <tr>
                            <th>Uživatelské jméno</th>
                            <th>Jméno</th>
                            <th>Příjmení</th>
                            <th>E-mail</th>
                            <th>Uživatelská role</th>
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
