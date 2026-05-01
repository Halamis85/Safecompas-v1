<!doctype html>
<html lang="cs-cz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <meta name="csp-nonce" content="{{ csp_nonce() }}">
    <title>Zapomenuté heslo – Safecompas</title>
    @include('partials.head-perf')
    @vite(['resources/css/app.css','resources/css/login.css', 'resources/js/app.js'])
</head>
<body class="login-page">
@include('partials.body-perf')
<main class="login-wrap">
    <section class="login-card" aria-labelledby="forgot-heading">
        <div class="login-brand">
            <img src="{{ asset('images/img.png') }}" alt="" width="64" height="64">
            <h1 id="forgot-heading">Safecompas</h1>
            <p class="text-muted">Obnova hesla</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
            <div class="forgot-link">
                <a href="{{ route('login') }}">Zpět na přihlášení</a>
            </div>
        @else
            <p class="text-muted" style="margin-bottom:1.25rem;font-size:.9rem;">
                Zadejte svůj e-mail a my vám zašleme odkaz pro reset hesla.
            </p>

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf

                <div class="form-group">
                    <label for="email">E-mailová adresa</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="form-control"
                        autocomplete="email"
                        value="{{ old('email') }}"
                        required
                        autofocus>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <button type="submit" class="btn-login">
                    <i class="fa fa-paper-plane"></i> Odeslat odkaz
                </button>

                <div class="forgot-link">
                    <a href="{{ route('login') }}">Zpět na přihlášení</a>
                </div>
            </form>
        @endif
    </section>
</main>

</body>
</html>
