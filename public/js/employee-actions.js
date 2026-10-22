document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[href*="c=Employee&a=edit&id="]').forEach(function (editLink) {
        const row = editLink.closest('tr');
        const id = new URL(editLink.href).searchParams.get('id');
        if (!row || !id) return;

        const name = row.querySelector('strong')?.textContent.trim() || 'este estudiante';
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn btn-sm btn-outline-danger ms-1';
        deleteButton.title = 'Eliminar registro';
        deleteButton.setAttribute('aria-label', 'Eliminar a ' + name);
        deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
        deleteButton.addEventListener('click', function () {
            const confirmed = window.confirm(
                '¿Eliminar definitivamente a ' + name + '?\n\n' +
                'También se eliminará su historial de asistencias. Esta acción no se puede deshacer.'
            );
            if (!confirmed) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?c=Employee&a=delete';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = id;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
        editLink.closest('td').appendChild(deleteButton);
    });
});
