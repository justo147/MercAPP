/**
 * @fileoverview Módulo de valoración de transacciones.
 * Gestiona el modal interactivo de estrellas, el cálculo de la media
 * y el envío AJAX de la valoración al servidor.
 *
 * @module valoracion
 * @requires bootstrap
 */

(function () {

    /**
     * Etiqueta <script> actual, usada para leer los atributos data-*.
     * @type {HTMLScriptElement}
     */
    const scriptTag = document.currentScript ||
                      document.querySelector('script[data-transaccion-id]');

    /**
     * ID de la transacción inyectado desde chat.php vía data-transaccion-id.
     * @type {number}
     */
    const TRANSACCION_ID = parseInt(scriptTag?.dataset?.transaccionId ?? 0);

    /**
     * ID del chat inyectado desde chat.php vía data-chat-id.
     * @type {number}
     */
    const CHAT_ID = parseInt(scriptTag?.dataset?.chatId ?? 0);

    /**
     * Inicializa el modal de valoración, los grupos de estrellas
     * y el listener del botón de envío.
     * Se ejecuta cuando el DOM está listo.
     *
     * @function init
     * @returns {void}
     */
    function init() {
        const modalEl = document.getElementById('modalValoracion');
        if (!modalEl) return;

        /**
         * Inicializa un grupo de estrellas interactivo.
         * Maneja los eventos de hover, salida y clic para actualizar
         * visualmente las estrellas y el campo hidden asociado.
         *
         * @function initStarGroup
         * @param {HTMLElement} group - Contenedor del grupo de estrellas
         *     con atributo data-field que indica el criterio
         *     (fiabilidad, comunicacion, puntualidad).
         * @returns {void}
         */
        function initStarGroup(group) {
            const stars  = group.querySelectorAll('.star-btn');
            const field  = group.dataset.field;
            const hidden = document.getElementById('inp-' + field);

            /** @type {number} Puntuación actualmente seleccionada (1-5). */
            let current = 0;

            /**
             * Pinta las estrellas hasta el índice indicado.
             * Las estrellas anteriores a `upTo` se muestran rellenas;
             * el resto, vacías.
             *
             * @function paint
             * @param {number} upTo - Número de estrellas a resaltar (1-5).
             * @returns {void}
             */
            function paint(upTo) {
                stars.forEach((s, idx) => {
                    s.classList.toggle('bi-star-fill', idx < upTo);
                    s.classList.toggle('bi-star',      idx >= upTo);
                    s.style.color = idx < upTo ? '#ffc107' : '#ccc';
                });
            }

            stars.forEach((star, idx) => {
                /** Resalta las estrellas al pasar el ratón. */
                star.addEventListener('mouseenter', () => paint(idx + 1));

                /** Restaura la puntuación seleccionada al salir el ratón. */
                star.addEventListener('mouseleave', () => paint(current));

                /**
                 * Fija la puntuación elegida, actualiza el input hidden,
                 * recalcula la media y comprueba si el formulario está listo.
                 */
                star.addEventListener('click', () => {
                    current      = idx + 1;
                    hidden.value = current;
                    paint(current);
                    updateResumen();
                    checkReady();
                });
            });
        }

        /**
         * Calcula y muestra la media de los tres criterios de valoración
         * (fiabilidad, comunicación y puntualidad).
         * Oculta el resumen si algún criterio aún no ha sido puntuado.
         *
         * @function updateResumen
         * @returns {void}
         */
        function updateResumen() {
            const f = parseInt(document.getElementById('inp-fiabilidad').value)   || 0;
            const c = parseInt(document.getElementById('inp-comunicacion').value) || 0;
            const p = parseInt(document.getElementById('inp-puntualidad').value)  || 0;

            if (f && c && p) {
                const media  = ((f + c + p) / 3).toFixed(1);
                const starsN = Math.round(media);
                document.getElementById('media-val').textContent   = media + ' / 5';
                document.getElementById('media-stars').textContent =
                    '★'.repeat(starsN) + '☆'.repeat(5 - starsN);
                document.getElementById('resumen-media').classList.remove('d-none');
            } else {
                document.getElementById('resumen-media').classList.add('d-none');
            }
        }

        /**
         * Habilita o deshabilita el botón de envío según si los tres
         * criterios de valoración han sido puntuados.
         *
         * @function checkReady
         * @returns {void}
         */
        function checkReady() {
            const f = parseInt(document.getElementById('inp-fiabilidad').value)   || 0;
            const c = parseInt(document.getElementById('inp-comunicacion').value) || 0;
            const p = parseInt(document.getElementById('inp-puntualidad').value)  || 0;
            document.getElementById('btn-enviar-valoracion').disabled = !(f && c && p);
        }

        /**
         * Envía la valoración al servidor mediante una petición AJAX (fetch).
         * Muestra feedback visual de éxito o error según la respuesta.
         * En caso de éxito, recarga la página tras 1,8 segundos.
         *
         * @listens HTMLButtonElement#click
         * @async
         * @returns {void}
         */
        document.getElementById('btn-enviar-valoracion').addEventListener('click', function () {
            const btn          = this;
            const feedback     = document.getElementById('rating-feedback');
            const fiabilidad   = document.getElementById('inp-fiabilidad').value;
            const comunicacion = document.getElementById('inp-comunicacion').value;
            const puntualidad  = document.getElementById('inp-puntualidad').value;
            const comentario   = document.getElementById('comentario-val').value.trim();

            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';

            /**
             * Cuerpo de la petición POST con los datos de la valoración.
             * @type {URLSearchParams}
             */
            const body = new URLSearchParams({
                valoracion:     '1',
                transaccion_id: TRANSACCION_ID,
                fiabilidad,
                comunicacion,
                puntualidad,
                comentario
            });

            fetch(`${BASE}/public/views/chat.php?id=${CHAT_ID}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    body.toString()
            })
            /**
             * @param {Response} r - Respuesta HTTP del servidor.
             * @returns {Promise<Object>} JSON parseado con { ok, error? }.
             */
            .then(r => r.json())

            /**
             * Gestiona la respuesta JSON del servidor.
             * @param {{ ok: boolean, error?: string }} data - Resultado del servidor.
             */
            .then(data => {
                feedback.classList.remove('d-none', 'alert-danger', 'alert-success');
                if (data.ok) {
                    feedback.classList.add('alert', 'alert-success');
                    feedback.textContent = '¡Gracias! Tu valoración ha sido enviada.';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1800);
                } else {
                    feedback.classList.add('alert', 'alert-danger');
                    feedback.textContent = data.error ?? 'Error al enviar la valoración.';
                    btn.disabled  = false;
                    btn.innerHTML = '<i class="bi bi-send me-1"></i> Enviar valoración';
                }
            })

            /** Gestiona errores de red o fallos en el fetch. */
            .catch(() => {
                feedback.classList.remove('d-none');
                feedback.classList.add('alert', 'alert-danger');
                feedback.textContent = 'Error de red. Inténtalo de nuevo.';
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i> Enviar valoración';
            });
        });

        /** Inicializa todos los grupos de estrellas encontrados en el DOM. */
        document.querySelectorAll('.star-group').forEach(initStarGroup);

        /**
         * Instancia y abre el modal de Bootstrap automáticamente.
         * Se configura con backdrop estático y sin cierre por teclado
         * para forzar al usuario a completar la valoración.
         *
         * @type {bootstrap.Modal}
         */
        const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
        modal.show();
    }

    /**
     * Espera a que el DOM esté completamente cargado antes de inicializar
     * el módulo, o lo ejecuta de inmediato si ya está disponible.
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();