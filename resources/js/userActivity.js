// resources/js/userActivity.js
import DataTable from 'datatables.net-dt';

let activitiesDataTable;

export function userActivity() {

    const activitiesTable = document.getElementById('activitiesTable');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');

    if (!activitiesTable) {
        console.warn('userActivity.js: Element #activitiesTable nebyl nalezen.');
        return;
    }
    if (!csrfToken) {
        console.error('userActivity.js: CSRF token nebyl nalezen!');
        return;
    }

    loadAndInitActivities();

    function loadAndInitActivities() {
        // Zrušit starou instanci DataTable (pokud existuje)
        if (activitiesDataTable) {
            activitiesDataTable.destroy();
            activitiesDataTable = null;
        }

        fetch('/userActivity', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content')
            }
        })
            .then(response => response.json().then(result => {
                if (!response.ok) {
                    throw new Error(result.message || result.error || 'Chyba při načítání aktivit.');
                }
                return result;
            }))
            .then(result => {
                // Controller vrací {status, data: [...]}, pro jistotu podpora i pole
                const activities = Array.isArray(result) ? result : (result.data || []);
                renderActivitiesTable(activities);
            })
            .catch(error => {
                console.error('Chyba při načítání aktivit:', error);
                const tbody = activitiesTable.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">
                        Chyba při načítání aktivit: ${error.message || 'Nelze se připojit k serveru.'}
                    </td></tr>`;
                }
            });
    }

    function renderActivitiesTable(activities) {
        activitiesDataTable = new DataTable(activitiesTable, {
            data: activities,
            pageLength: 25,
            order: [[0, 'desc']],
            columns: [
                {
                    data: 'timestamp',
                    title: 'Datum a čas',
                    className: 'text-center',
                    render: function (data) {
                        if (!data) return '';
                        const d = new Date(data);
                        return isNaN(d.getTime()) ? data : d.toLocaleString('cs-CZ');
                    }
                },
                {
                    data: null,
                    title: 'Uživatel',
                    className: 'text-center',
                    render: function (row) {
                        const name = `${row.firstname || ''} ${row.lastname || ''}`.trim();
                        return name || '—';
                    }
                },
                {
                    data: 'activity_type',
                    title: 'Typ aktivity',
                    className: 'text-center',
                    defaultContent: '—'
                },
                {
                    data: 'details',
                    title: 'Detaily',
                    className: 'text-start',
                    defaultContent: '—'
                }
            ],
            dom:
                "<'row align-items-center mb-2 me-2'<'col text-end'f>>" +
                "<'table-responsive't>" +
                "<'row align-items-center mt-2 mb-2'<'col text-center'p>>",
            info: false,
            paging: true,
            responsive: true,
            searching: true,
            language: {
                url: '/assets/cs.json'
            }
        });
    }
}