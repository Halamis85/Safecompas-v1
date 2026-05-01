<!doctype html>
<html lang="cs-cz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <meta name="csp-nonce" content="{{ csp_nonce() }}">
    <title>Přihlášení – Safecompas</title>
    @include('partials.head-perf') 
    @vite(['resources/css/app.css','resources/css/login.css', 'resources/js/app.js', 'resources/js/login.js'])
</head>
<body class="login-page">
@include('partials.body-perf')
<main class="login-wrap">
    <section class="login-card" aria-labelledby="login-heading">
        <div class="login-brand">
            <img src="{{ asset('images/img.png') }}" alt="" width="64" height="64">
            <h1 id="login-heading">Safecompas</h1>
            <p class="text-muted">Přihlášení do aplikace</p>
        </div>

        <form method="POST" action="{{ url('/login') }}" novalidate>
            @csrf

            <div class="form-group">
                <label for="username">Uživatelské jméno</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    class="form-control"
                    autocomplete="username"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    value="{{ old('username') }}"
                    required
                    autofocus>
            </div>

            <div class="form-group">
                <label for="password">Heslo</label>
                <div class="input-with-toggle">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="form-control"
                        autocomplete="current-password"
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

            @if(session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <button type="submit" class="btn-login">
                <i class="fa fa-sign-in-alt"></i> Přihlásit se
            </button>

            {{-- Self-service password reset --}}
            <div class="forgot-link">
                <a href="{{ route('password.forgot') }}">Zapomněli jste heslo?</a>
            </div>
        </form>
    </section>
</main>

</body>
</html>