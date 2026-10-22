document.addEventListener('DOMContentLoaded', function () {
    addShiftFilter();
    addShiftTableColumn();
});

function addShiftFilter() {
    const filterForm = document.querySelector('form[method="GET"]');
    if (!filterForm || filterForm.querySelector('[name="shift"]')) return;

    const current = new URLSearchParams(window.location.search).get('shift') || '';
    const field = document.createElement('div');
    field.className = 'col-sm-6 col-lg-4';
    field.innerHTML = '<label class="form-label small fw-semibold">Turno</label><select name="shift" class="form-select"><option value="">Todos los turnos</option><option value="manana"' + (current === 'manana' ? ' selected' : '') + '>Mañana</option><option value="tarde"' + (current === 'tarde' ? ' selected' : '') + '>Tarde</option></select>';
    const actions = filterForm.querySelector('.col-12');
    filterForm.insertBefore(field, actions);
}

function addShiftTableColumn() {
    const table = document.querySelector('#bulkCardsForm table');
    if (!table || table.querySelector('th[data-column="shift"]')) return;

    const statusHeader = Array.from(table.querySelectorAll('thead th')).find(function (header) {
        return header.textContent.trim().toLowerCase() === 'estado';
    });
    if (!statusHeader) return;

    const shiftHeader = document.createElement('th');
    shiftHeader.dataset.column = 'shift';
    shiftHeader.textContent = 'Turno';
    statusHeader.before(shiftHeader);

    table.querySelectorAll('tbody tr').forEach(function (row) {
        const checkbox = row.querySelector('.student-card-check');
        const statusCell = row.querySelector('.badge.text-bg-success, .badge.text-bg-secondary')?.closest('td');
        if (!checkbox || !statusCell) return;

        const shift = window.employeeShifts?.[checkbox.value] || '';
        const label = shift === 'manana' ? 'MAÑANA' : (shift === 'tarde' ? 'TARDE' : 'SIN ASIGNAR');
        const cell = document.createElement('td');
        cell.innerHTML = '<span class="badge text-bg-info">' + label + '</span>';
        statusCell.before(cell);
    });
}
