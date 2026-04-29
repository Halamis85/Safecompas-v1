<!DOCTYPE html>
<html lang="cs-cz">
<head>
    <meta charset="utf-8">
    <meta name="user-authenticated" content="{{ session('user') ? 'true' : 'false' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-lifetime" content="{{ (int) config('session.lifetime') * 60 }}">
    <meta name="current-user-id" content="{{ session('user.id', '') }}">
    <meta name="csp-nonce" content="{{ csp_nonce() }}">
    <meta name="current-user-is-super-admin" content="{{ session('user.is_super_admin') ? 'true' : 'false' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aplikace pro správu OOPP.">
    <meta name="robots" content="noindex">
    <meta name="author" content="Lukáš Halamka for Safecompas">
    <title>@yield('title', 'Safecompas')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body id="app-body" class="d-flex flex-column vh-100 @yield('body-class')" data-page="">
<header class="navbar navbar-expand-lg border-bottom sticky-top">
    <div class="container-fluid">
        <nav class="d-flex w-100 justify-content-between align-items-center">
            <a class="navbar-brand" href="/">
                <img src="{{asset('images/img.png') }}" style="width: 50px;height: 50px" alt="Logo"> Safecompas
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/new_orders">Nová objednávka OOPP</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/prehled">Přehled OOPP k vydání</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/cards">Karta zaměstnance</a>
                    </li>
                </ul>
            </div>
            {{-- Notifikační zvonek - pouze pro přihlášené --}}
            @if(session('user'))
                @include('components.notifica-bell')
            @endif
            <div class="tooltip-container">
                <button class="nav-link" id="profileSidebarToggle">
                    <img src="{{asset('images/menu_open.webp') }}" alt="menu icon" style="width: 90px; height: 90px;" class="navbar-avatar-img">
                </button>
                <span class="tooltip-text">Toto je vstup do hlavního menu </span>
            </div>
        </nav>
    </div>
</header>

<div class="profile-sidebar" id="profileSidebar" aria-label="hlavni menu">
    <div class="sidebar-header">
        <img src="{{asset('images/cons.png') }}" alt="Fotka uživatele" class="rounded-circle sidebar-avatar-img">
        <h5 class="mb-0 mt-2"></h5>
        <p class="role"></p>
        <button class="btn-close" aria-label="Close" id="closeSidebar"></button>
    </div>
    <div class="sidebar-body">
        <button class="sidebar-item text-center w-100 " id="theme-toggle" aria-label="zapnutí světelného režimu">
            <i class="fa-solid fa-sun"></i> Režim
        </button>
        <a class="sidebar-item text-center" href="/admin" aria-label="přesun do administrace">
            <i class="fa-solid fa-id-card me-1"></i> Administrace
        </a>
        <a class="sidebar-item text-center" href="/lekarnicke" aria-label="přesun do správy lékárniček">
            <i class="fa-solid fa-kit-medical me-1"></i>Správa lékárniček
        </a>
    </div>
    <button class="sidebar-item mt-2 logout-item text-center w-100" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="fa-solid fa-right-from-bracket me-1"></i> Odhlásit se
    </button>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<main id="main" class="flex-grow-1">
    @yield('content')
</main>
<footer class="footer">
    <div class="text-footer">
        <p> &copy; {{date('Y')}} Safecompas verze <span>{{ config('app.version') }}</span></p>
    </div>
    <div class="icon-link">
        <a href="https://www.facebook.com"><i class="fa-brands fa-facebook fs-3"></i></a>
        <a href="https://www.instagram.com"><i class="fa-brands fa-instagram fs-3"></i></a>
        <a href="https://www.linkedin.com"><i class="fa-brands fa-linkedin fs-3"></i></a>
        <a href="https://www.twitter.com"><i class="fa-brands fa-square-x-twitter fs-3"></i></a>
    </div>
</footer>

<!-- Vysklaovací okno pro odhlášení -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="modal-header">
                <h2 class="text-center" id="logoutModalLabel">Odhlášení</h2>
            </div>
            <div class="modal-body text-center fs-5">
                <p> opravdu si přejete odhlásit z modulu OOPP ?</p>
            </div>
            <div class="modal-footer d-flex align-items-center justify-content-center">
                <button type="button" class="btn btn-secondary me-3" data-bs-dismiss="modal">Zrušit odhlášení</button>
                <a class="btn btn-danger ms-3" href="/logout">Odhlásit se</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Varování před automatickým odhlášením -->
<div class="modal fade" id="sessionWarningModal" tabindex="-1"
     aria-labelledby="sessionWarningModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="modal-header border-0">
                <h2 class="text-center w-100" id="sessionWarningModalLabel">
                    <i class="fa-solid fa-clock text-warning me-2"></i>
                    Brzy budete odhlášen
                </h2>
            </div>
            <div class="modal-body text-center fs-5">
                <p>Z důvodu nečinnosti budete automaticky odhlášen za:</p>
                <p class="display-4 fw-bold text-warning my-3" id="sessionCountdown" aria-live="polite">2:00</p>
                <p class="text-muted small mb-0">Klikněte na „Zůstat přihlášen" pro pokračování v práci.</p>
            </div>
            <div class="modal-footer d-flex align-items-center justify-content-center border-0">
                <a class="btn btn-outline-secondary me-3" href="/logout">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Odhlásit se
                </a>
                <button type="button" class="btn btn-success ms-3" id="stayLoggedBtn">
                    <i class="fa-solid fa-check me-1"></i> Zůstat přihlášen
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
