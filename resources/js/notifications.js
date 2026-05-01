// resources/js/notifications.js
// Zvonek s notifikacemi pro každého přihlášeného uživatele.
// Notifikace jsou per-user (každý vidí jen vlastní).

class NotificationBell {
    constructor() {
        this.container   = document.getElementById('notifications-container');
        this.badge       = document.getElementById('notification-badge');
        this.markAllBtn  = document.getElementById('mark-all-read');
        this.loadingEl   = document.getElementById('loading-notifications');

        const csrfMeta   = document.querySelector('meta[name="csrf-token"]');
        this.csrfToken   = csrfMeta ? csrfMeta.getAttribute('content') : '';

        this.intervalId  = null;
        this.stopped     = false;

        this.init();
    }

    init() {
        this.loadNotifications();
        this.setupEventListeners();
        this.intervalId = setInterval(() => this.loadNotifications(), 30_000);
    }

    stop() {
        this.stopped = true;
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    hideBell() {
        const dropdown = document.querySelector('.notification-dropdown');
        if (dropdown) dropdown.style.display = 'none';
    }

    setupEventListeners() {
        if (this.markAllBtn) {
            this.markAllBtn.addEventListener('click', () => this.markAllAsRead());
        }
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.addEventListener('shown.bs.dropdown', () => this.loadNotifications());
        }
        if (this.container) {
            this.container.addEventListener('click', (e) => {
                const item = e.target.closest('[data-action="mark-read"]');
                if (item) this.markAsRead(item.dataset.id);
            });
        }
    }

    async loadNotifications() {
        if (this.stopped) return;

        let response;
        try {
            response = await fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
        } catch (error) {
            return;
        }

        if (response.status === 401) {
            this.stop();
            return;
        }

        if (response.status === 403) {
            this.stop();
            this.hideBell();
            return;
        }

        if (!response.ok) {
            this.showError();
            return;
        }

        let data;
        try {
            data = await response.json();
        } catch (e) {
            this.showError();
            return;
        }

        this.renderNotifications(data.notifications || []);
        this.updateBadge(data.unread_count || 0);
    }

    /**
     * Render media slot - obrázek pokud existuje (pro OOPP),
     * jinak Font Awesome ikona (pro lékárničky).
     */
    renderMedia(data) {
        if (data.img) {
            return `<img src="/images/OOPP/${encodeURIComponent(data.img.trim())}" alt="" class="rounded-circle produck-circle-notific" style="width: 50px; height: 50px; object-fit: contain;">`;
        }
        const icon = data.icon || 'fa-solid fa-bell';
        return `
            <div class="notification-fa-icon rounded-circle produck-circle-notific d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="${this.escape(icon)} fa-lg"></i>
            </div>
        `;
    }

    renderNotifications(notifications) {
        if (this.loadingEl) this.loadingEl.style.display = 'none';
        if (!this.container) return;

        if (notifications.length === 0) {
            this.container.innerHTML = `
                <div class="dropdown-item-text text-center text-muted py-3">
                    <i class="fas fa-bell-slash mb-2"></i><br>
                    Žádné nové notifikace
                </div>
            `;
            return;
        }

        const html = notifications.map(notification => {
            const isUnread = !notification.read_at;
            const data     = notification.data || {};
            const color    = data.color || '';
            const message  = this.escape(data.message || '');
            const empName  = this.escape(data.employee_name || '');
            const prodName = this.escape(data.product_name || '');
            const size     = this.escape(data.size || '');
            const media    = this.renderMedia(data);

            return `
                <div class="notification-item ${isUnread ? 'unread' : 'read'}"
                     data-id="${notification.id}"
                     data-action="mark-read">
                    <div class="d-flex">
                        <div class="notification-icon${color}">
                            ${media}
                        </div>
                        <div class="flex-grow-1">
                            <div class="notification-content">
                                <strong class="fs-8">${message}</strong><br>
                                <small>${empName}</small><br>
                                <small class="text-muted">${prodName}${size ? ' (' + size + ')' : ''}</small>
                            </div>
                            <div class="notification-time mt-1">
                                <i class="fas fa-clock"></i> ${this.escape(notification.created_at || '')}
                            </div>
                        </div>
                        ${isUnread ? '<div class="text-primary"><i class="fas fa-circle" style="font-size: 8px;"></i></div>' : ''}
                    </div>
                </div>
            `;
        }).join('');

        this.container.innerHTML = html;
    }

    escape(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    updateBadge(count) {
        if (!this.badge) return;
        if (count > 0) {
            this.badge.textContent = count > 99 ? '99+' : count;
            this.badge.style.display = 'block';
        } else {
            this.badge.style.display = 'none';
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ id: notificationId }),
            });
            if (response.ok) this.loadNotifications();
        } catch (error) {
            // tiché
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (response.ok) this.loadNotifications();
        } catch (error) {
            // tiché
        }
    }

    showError() {
        if (this.container) {
            this.container.innerHTML = `
                <div class="dropdown-item-text text-center text-danger py-3">
                    <i class="fas fa-exclamation-triangle mb-2"></i><br>
                    Chyba při načítání notifikací
                </div>
            `;
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const userMeta   = document.querySelector('meta[name="user-authenticated"]');
    const isAuth     = userMeta && userMeta.content === 'true';
    const bellExists = document.getElementById('notifications-container') !== null;

    if (isAuth && bellExists) {
        window.notificationBell = new NotificationBell();
    }
});
