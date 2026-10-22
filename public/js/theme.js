(function () {
    const root = document.documentElement;
    let savedTheme = null;

    try {
        savedTheme = localStorage.getItem('sistema-acceso-theme');
    } catch (error) {
        console.warn('No se pudo leer la preferencia de tema guardada.', error);
    }

    const initialTheme = savedTheme === 'dark' || savedTheme === 'light'
        ? savedTheme
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    root.dataset.theme = initialTheme;

    function refreshThemeButtons() {
        const isDark = root.dataset.theme === 'dark';
        document.querySelectorAll('.theme-toggle').forEach((button) => {
            button.innerHTML = isDark
                ? '<i class="bi bi-sun-fill" aria-hidden="true"></i>' + (button.classList.contains('navbar-theme-toggle') ? '' : '<span> Modo diurno</span>')
                : '<i class="bi bi-moon-stars-fill" aria-hidden="true"></i>' + (button.classList.contains('navbar-theme-toggle') ? '' : '<span> Modo nocturno</span>');
            const label = isDark ? 'Cambiar al modo diurno' : 'Cambiar al modo nocturno';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.setAttribute('aria-pressed', String(isDark));
        });
    }

    function toggleTheme() {
        const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = nextTheme;
        try {
            localStorage.setItem('sistema-acceso-theme', nextTheme);
        } catch (error) {
            console.warn('No se pudo guardar la preferencia de tema.', error);
        }
        refreshThemeButtons();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.theme-toggle').forEach((button) => {
            button.addEventListener('click', toggleTheme);
        });
        refreshThemeButtons();
    });
})();
