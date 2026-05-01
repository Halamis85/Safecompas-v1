<!doctype html>
<html lang="cs-cz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <meta name="csp-nonce" content="{{ csp_nonce() }}">
    <title>Nastavení nového hesla – Safecompas</title>
    @include('partials.head-perf')
    @vite(['resources/css/app.css','resources/css/login.css', 'resources/js/app.js', 'resources/js/login.js'])
</head>
<body class="login-page">
@include('partials.body-perf')
<main class="login-wrap">
    <section class="login-card" aria-labelledby="reset-heading">
        <div class="login-brand">
            <img src="{{ asset('images/img.png') }}" alt="" width="64" height="64">
            <h1 id="reset-heading">Safecompas</h1>
            <p class="text-muted">Nastavení nového hesla</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="password">Nové heslo</label>
                <div class="input-with-toggle">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="form-control"
                        autocomplete="new-password"
                        required
                        autofocus>
                    <button type="button" class="toggle-password" aria-label="Zobrazit heslo">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <small class="text-muted">
                    Min. 12 znaků, velká i malá písmena, číslo a speciální znak.
                </small>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Potvrzení hesla</label>
                <div class="input-with-toggle">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="form-control"
                        autocomplete="new-password"
                        required>
                    <button type="button" class="toggle-password" aria-label="Zobrazit heslo">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <button type="submit" class="btn-login">
                <i class="fa fa-key"></i> Uložit nové heslo
            </button>

            <div class="forgot-link">
                <a href="{{ route('login') }}">Zpět na přihlášení</a>
            </div>
        </form>
    </section>
</main>

</body>
</html>
