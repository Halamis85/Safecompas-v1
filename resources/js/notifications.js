class NotificationBell {
    constructor() {
        this.container = document.getElementById('notifications-container');
        this.badge = document.getElementById('notification-badge');
        this.markAllBtn = document.getElementById('mark-all-read');
        this.loadingEl = document.getElementById('loading-notifications');

        this.init();
    }

    init() {
        this.loadNotifications();
        this.setupEventListeners();

        // Auto refresh každých 30 sekund
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }

    setupEventListeners() {
        // Mark all as read
        if (this.markAllBtn) {
            this.markAllBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }

        // Refresh při otevření dropdown
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.addEventListener('shown.bs.dropdown', () => {
                this.loadNotifications();
            });
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load notifications');
            }

            const data = await response.json();
            this.renderNotifications(data.notifications);
            this.updateBadge(data.unread_count);

        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showError();
        }
    }

    renderNotifications(notifications) {
        if (this.loadingEl) {
            this.loadingEl.style.display = 'none';
        }

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

        const notificationsHtml = notifications.map(notification => {
            const isUnread = !notification.read_at;
            const data = notification.data;

            return `
                <div class="notification-item ${isUnread ? 'unread' : 'read'}"
                     data-id="${notification.id}"
                     onclick="notificationBell.markAsRead('${notification.id}')">
                    <div class="d-flex">
                        <div class="notification-icon${data.color}">
                            <img src="/images/OOPP/${data.img}" alt="Produkt obrazek" class="rounded-circle produck-circle-notific" style="width: 50px; height: 50px; object-fit: contain;">
                        </div>
                        <div class="flex-grow-1">
                            <div class="notification-content">
                                <strong class="fs-8">${data.message}</strong><br>
                                <small>${data.employee_name}</small><br>
                                <small class="text-muted">${data.product_name} (${data.size})</small>
                            </div>
                            <div class="notification-time mt-1">
                                <i class="fas fa-clock"></i> ${notification.created_at}
                            </div>
                        </div>
                        ${isUnread ? '<div class="text-primary"><i class="fas fa-circle" style="font-size: 8px;"></i></div>' : ''}
                    </div>
                </div>
            `;
        }).join('');

        this.container.innerHTML = notificationsHtml;
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ id: notificationId })
            });

            if (response.ok) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
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

// Inicializace po načtení stránky
document.addEventListener('DOMContentLoaded', function() {
    window.notificationBell = new NotificationBell();
});
