/**
 * @fileoverview Módulo de notificaciones de mensajes no leídos.
 * Actualiza automáticamente el badge del icono de mensajes
 * consultando el servidor cada 5 segundos.
 *
 * @module badge_mensajes
 */

document.addEventListener("DOMContentLoaded", () => {

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

});