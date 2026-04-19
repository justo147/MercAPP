/**
 * @fileoverview Módulo de notificaciones de mensajes no leídos.
 * Actualiza automáticamente el badge del icono de mensajes
 * consultando el servidor cada 5 segundos.
 *
 * @module badge_mensajes
 */

/* ── Notificaciones ──────────────────────────────────────── */

let _ultimoIdNotif = 0;

function renderNotificaciones(items) {
    const lista  = document.getElementById("notif-lista");
    const vacio  = document.getElementById("notif-vacio");
    const badge  = document.getElementById("badge-notif");
    if (!lista) return;

    if (!items || items.length === 0) {
        lista.innerHTML = '<li class="px-3 py-2 text-muted small" id="notif-vacio">Sin notificaciones nuevas</li>';
        if (badge) badge.style.display = "none";
        return;
    }

    if (badge) {
        badge.textContent   = items.length > 9 ? "9+" : items.length;
        badge.style.display = "inline-block";
    }

    const iconos = {
        mensaje:      "bi-envelope",
        coincidencia: "bi-stars",
        valoracion:   "bi-star",
        moderacion:   "bi-shield-exclamation",
    };

    lista.innerHTML = items.map(n => {
        const ic = iconos[n.tipo] ?? "bi-bell";
        const ts = new Date(n.fecha).toLocaleString("es-ES", { day:"2-digit", month:"short", hour:"2-digit", minute:"2-digit" });
        return `<li>
            <div class="dropdown-item d-flex gap-2 align-items-start py-2 border-bottom"
                 style="white-space:normal; cursor:default;">
                <i class="bi ${ic} text-primary mt-1 flex-shrink-0"></i>
                <div>
                    <div class="small">${n.contenido}</div>
                    <div class="text-muted" style="font-size:.7rem;">${ts}</div>
                </div>
            </div>
        </li>`;
    }).join('');

    // Mostrar toasts para notificaciones nuevas (id mayor al último conocido)
    items
        .filter(n => parseInt(n.id) > _ultimoIdNotif)
        .slice(0, 3)
        .forEach(n => mostrarToast(n.contenido, n.tipo));

    _ultimoIdNotif = Math.max(...items.map(n => parseInt(n.id)));
}

function marcarNotificacionesLeidas() {
    fetch(`${BASE}/api/notificaciones.php`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "accion=mark_all"
    }).then(() => {
        const badge = document.getElementById("badge-notif");
        if (badge) badge.style.display = "none";
    });
}

function actualizarNotificaciones() {
    fetch(`${BASE}/api/notificaciones.php`)
        .then(r => r.json())
        .then(data => renderNotificaciones(data.items || []))
        .catch(() => {});
}

function mostrarToast(mensaje, tipo = "info") {
    let contenedor = document.getElementById("toast-container");
    if (!contenedor) {
        contenedor = document.createElement("div");
        contenedor.id = "toast-container";
        contenedor.className = "toast-container position-fixed bottom-0 end-0 p-3";
        contenedor.style.zIndex = "1090";
        document.body.appendChild(contenedor);
    }

    const colores = { mensaje:"primary", valoracion:"warning", coincidencia:"success", moderacion:"danger" };
    const color   = colores[tipo] ?? "secondary";

    const div = document.createElement("div");
    div.className = `toast align-items-center text-bg-${color} border-0`;
    div.setAttribute("role", "alert");
    div.innerHTML = `
        <div class="d-flex">
            <div class="toast-body small">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
        </div>`;
    contenedor.appendChild(div);

    const t = new bootstrap.Toast(div, { delay: 5000 });
    t.show();
    div.addEventListener("hidden.bs.toast", () => div.remove());
}

document.addEventListener("DOMContentLoaded", () => {

    // Marcar como leídas al abrir el dropdown de notificaciones
    const notifBtn = document.getElementById("notif-btn");
    if (notifBtn) {
        notifBtn.addEventListener("click", () => {
            marcarNotificacionesLeidas();
        });
    }


    /**
     * Consulta el número de mensajes no leídos del usuario actual
     * y actualiza todos los badges con id="badge-mensajes" en el DOM.
     * Si el contador es 0, oculta el badge; si es mayor, lo muestra.
     *
     * @async
     * @function actualizarBadgeMensajes
     * @returns {void}
     */
    function actualizarBadgeMensajes() {
        fetch(`${BASE}/api/chat_unread_count.php`)

        /**
         * @param {Response} res - Respuesta HTTP del servidor.
         * @returns {Promise<string>} Texto plano con el número de mensajes no leídos.
         */
        .then(res => res.text())

        /**
         * Actualiza la visibilidad y el contenido de los badges
         * según el número de mensajes no leídos recibido.
         *
         * @param {string} num - Número de mensajes no leídos como string.
         * @returns {void}
         */
        .then(num => {
            const badges = document.querySelectorAll("#badge-mensajes");

            /** @type {number} Mensajes no leídos parseados. 0 si la respuesta no es numérica. */
            const count = parseInt(num) || 0;

            /**
             * Muestra u oculta cada badge según el contador.
             * @param {HTMLElement} badge - Elemento badge a actualizar.
             */
            badges.forEach(badge => {
                if (count > 0) {
                    badge.textContent      = count;
                    badge.style.display    = "inline-block";
                    badge.style.opacity    = "1";
                } else {
                    badge.style.display    = "none";
                    badge.style.opacity    = "0";
                }
            });
        })

        /**
         * Captura y registra en consola cualquier error de red
         * o fallo en el procesamiento de la respuesta.
         *
         * @param {Error} err - Error producido durante el fetch.
         */
        .catch(err => console.error("Error actualizando badge:", err));
    }

    /**
     * Ejecuta la primera actualización del badge al cargar la página
     * y establece un intervalo para repetirla cada 5 segundos.
     *
     * @type {number} ID del intervalo, retornado por setInterval.
     */
    actualizarBadgeMensajes();
    setInterval(actualizarBadgeMensajes, 5000);

    // Notificaciones: primera carga inmediata, polling cada 30s
    actualizarNotificaciones();
    setInterval(actualizarNotificaciones, 30000);

});