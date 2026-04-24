

<div class="dropdown notification-dropdown ">
    <button class="btn btn-outline-secondary position-relative" type="button" id="notificationDropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="display: none;">
                  0
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end notification-menu overflow-x-hidden" aria-labelledby="notificationDropdown">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Notifikace</h6>
            <button id="mark-all-read" class="all-select-btn"><svg class="icon" viewBox="0 0 24 24" fill="none"
                                    stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="4" ry="4"></rect>
                                    <polyline points="7 13 10 16 17 9"></polyline></svg>
                                    <span>Označit vše</span></button>
        </div>

        <div id="notifications-container" class="notification-list">
            <div class="dropdown-item-text text-center text-muted" id="loading-notifications">
                <i class="fas fa-spinner fa-spin"></i> Načítám...
            </div>
        </div>
        <a class="dropdown-item text-center" href="{{ url('/prehled') }}">
            <i class="fas fa-download"></i>
            <small class="fs5 mx-2">Zobrazit všechny objednávky</small>
            <i class="fas fa-download"></i>
        </a>
    </div>
</div>

