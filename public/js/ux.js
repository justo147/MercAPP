/**
 * ux.js — Utilidades globales de UX para MercApp
 * Cargado en todas las páginas autenticadas a través de navbar.php
 */

/* ── Loading state para botones ─────────────────────────────── */

/**
 * Pone un botón en estado de carga (spinner + texto + disabled).
 * Guarda el HTML original para restaurarlo con btnReset().
 */
function btnLoading(btn, texto) {
    if (!btn || btn.disabled) return;
    btn._origHtml    = btn.innerHTML;
    btn._origDisabled = btn.disabled;
    const label = texto || btn.dataset.loadingText || 'Enviando…';
    btn.disabled  = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${label}`;
}

/**
 * Restaura un botón al estado previo a btnLoading().
 */
function btnReset(btn) {
    if (!btn || btn._origHtml === undefined) return;
    btn.disabled  = btn._origDisabled;
    btn.innerHTML = btn._origHtml;
    delete btn._origHtml;
    delete btn._origDisabled;
}

/* ── Auto loading state en submit de formularios ─────────────── */

document.addEventListener('DOMContentLoaded', () => {

    // Aplica a cualquier form que NO tenga data-no-loading
    document.querySelectorAll('form:not([data-no-loading])').forEach(form => {
        form.addEventListener('submit', function (e) {
            const btn = this.querySelector('[type="submit"]');
            if (btn && !btn.disabled) btnLoading(btn);
        });
    });

    /* ── Detección offline ─────────────────────────────────────── */

    const banner = document.createElement('div');
    banner.id = 'offline-banner';
    banner.setAttribute('role', 'alert');
    banner.setAttribute('aria-live', 'assertive');
    banner.style.cssText = [
        'display:none',
        'position:fixed',
        'top:0',
        'left:0',
        'right:0',
        'z-index:9999',
        'background:#dc3545',
        'color:#fff',
        'text-align:center',
        'padding:.5rem 1rem',
        'font-size:.875rem',
        'font-weight:500',
    ].join(';');
    banner.innerHTML = '<i class="bi bi-wifi-off me-2"></i>Sin conexión a internet — comprueba tu red.';
    document.body.prepend(banner);

    const mostrarOffline = () => { banner.style.display = 'block'; };
    const ocultarOffline  = () => {
        banner.style.display = 'none';
        if (typeof mostrarToast === 'function') {
            mostrarToast('Conexión restablecida.', 'success');
        }
    };

    window.addEventListener('offline', mostrarOffline);
    window.addEventListener('online',  ocultarOffline);
    if (!navigator.onLine) mostrarOffline();

    /* ── Aviso de cambios sin guardar ────────────────────────────
       Aplica a formularios con el atributo data-unsaved-warning    */

    document.querySelectorAll('form[data-unsaved-warning]').forEach(form => {
        let modificado = false;

        form.addEventListener('input',  () => { modificado = true; });
        form.addEventListener('change', () => { modificado = true; });
        form.addEventListener('submit', () => { modificado = false; });

        window.addEventListener('beforeunload', e => {
            if (!modificado) return;
            e.preventDefault();
            e.returnValue = '¿Seguro que quieres salir? Los cambios no guardados se perderán.';
        });
    });

    /* ── Tooltips en texto truncado ──────────────────────────────
       Añade title automáticamente a elementos con .text-truncate   */

    document.querySelectorAll('.text-truncate').forEach(el => {
        if (!el.title && el.textContent.trim()) {
            el.title = el.textContent.trim();
        }
    });

    /* ── Inicializar tooltips de Bootstrap ───────────────────────*/

    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }

});
