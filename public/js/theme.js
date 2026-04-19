const toggleBtn = document.getElementById('themeToggle');
const body = document.body;

// Aplica el tema: primero del atributo data-theme (PHP/session), luego localStorage
function applyTheme(theme) {
    if (theme === 'dark') {
        body.classList.add('dark-mode');
        if (toggleBtn) toggleBtn.textContent = '☀️';
    } else {
        body.classList.remove('dark-mode');
        if (toggleBtn) toggleBtn.textContent = '🌙';
    }
}

try {
    // data-theme se inyecta desde PHP leyendo $_SESSION['theme']
    const serverTheme = document.documentElement.dataset.theme;
    const savedTheme  = serverTheme || localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
} catch (e) {
    // sin acceso a localStorage ni data-theme: modo claro por defecto
}

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        const dark = body.classList.toggle('dark-mode');
        const theme = dark ? 'dark' : 'light';
        toggleBtn.textContent = dark ? '☀️' : '🌙';

        try { localStorage.setItem('theme', theme); } catch (_) {}

        // Persistir en sesión del servidor (best-effort)
        if (typeof BASE !== 'undefined') {
            fetch(`${BASE}/api/set_theme.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `theme=${theme}`
            }).catch(() => {});
        }
    });
}
