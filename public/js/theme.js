// Theme system — uses data-theme attribute on <html> (set by base.html.twig before paint)
// This file handles the toggle button click only; initial theme is applied inline in base.html.twig.

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;

    function updateIcon(dark) {
        const icon = document.getElementById('themeIcon') || btn.querySelector('i');
        if (icon) {
            icon.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }
    }

    // Sync icon with current theme
    updateIcon(document.documentElement.getAttribute('data-theme') === 'dark');

    btn.addEventListener('click', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const next   = isDark ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', next);
        updateIcon(next === 'dark');

        try { localStorage.setItem('mercapp_theme', next); } catch (_) {}

        if (typeof BASE !== 'undefined') {
            fetch(`${BASE}/api/set_theme.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `theme=${next}`
            }).catch(() => {});
        }
    });
});
