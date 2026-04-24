<!DOCTYPE html>
<html lang="cs-cz">
<head>
    <meta charset="utf-8">
    <meta name="user-authenticated" content="{{ session('user') ? 'true' : 'false' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            @include('components.notifica-bell')
            <div class="tooltip-container">
                <button class="nav-link" id="profileSidebarToggle">
                    <img src="{{asset('images/menu_open.webp') }}" alt="menu icon" style="width: 90px; height: 90px;" class="navbar-avatar-img">
                </button>
                <span class="tooltip-text">Toto je vstup do hlavního menu </span>
            </div>
        </nav>
    </div>
</header>

<div class="profile-sidebar" id="profileSidebar">
    <div class="sidebar-header">
        <img src="{{asset('images/cons.png') }}" alt="Fotka uživatele" class="rounded-circle sidebar-avatar-img">
        <h5 class="mb-0 mt-2"></h5>
        <p class="role"></p>
        <button class="btn-close" aria-label="Close" id="closeSidebar"></button>
    </div>
    <div class="sidebar-body">
        <a class="sidebar-item text-center" href="/settings">
            <i class="fa-solid fa-gear me-1"></i> Nastavení
        </a>
        <a id="theme-toggle" class="sidebar-item text-center">
            <i class="fa-solid fa-sun"></i> Režim
        </a>
        <a class="sidebar-item text-center" href="/admin">
            <i class="fa-solid fa-id-card me-1"></i> Administrace
        </a>
        <a href="/lekarnicke" class="sidebar-item text-center">
            <i class="fa-solid fa-kit-medical fa-2x pb-2"></i>Správa lékárniček</a>
    </div>
    <a class="sidebar-item mt-2 logout-item text-center" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="fa-solid fa-right-from-bracket me-1"></i> Odhlásit se
    </a>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<main id="main" class="flex-grow-1">
    @yield('content')
</main>
<footer class="footer">
    <div class="text-footer">
        <p> &copy; 2025 Safecompas verze 1.0.1 modul pro OOPP</p>
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
</body>
</html>


