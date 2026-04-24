<!doctype html>
<html lang="cs-cz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{asset('css/style-admin.css')}}" type="text/css">
    <title>Přihlášení</title>
</head>
<body>
<div class="row">
    <div class="col-md-8 mx-auto p-0">
        <div class="card border-0">
            <div class="login-box border-0">
                <div class="login-snip">
                    <form method="POST" action="{{ url('/login') }}">
                        @csrf
                        <input id="tab-1" type="radio" name="tab" class="sign-in" checked>
                        <label for="tab-1" class="tab"></label>
                        <div class="login-space">
                            <div class="group">
                                <label for="username" class="label">Uživatelské jméno</label>
                                <input id="username" name="username" type="text" class="input"
                                       placeholder="Napiš uživatelské jméno" required>
                            </div>
                            <div class="group">
                                <label for="password" class="label">Heslo</label>
                                <input id="password" name="password" type="password" class="input"
                                       placeholder="Zadej heslo" required>
                            </div>
                            @if($errors->any())
                                <div class="alert alert-danger text-center mt-3" role="alert">
                                    @foreach($errors->all() as $error)
                                        {{ $error }}<br>
                                    @endforeach
                                </div>
                            @endif
                            <div class="hr"></div>
                            <div class="group">
                                <input type="submit" class="button" value="Přihlásit se">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

