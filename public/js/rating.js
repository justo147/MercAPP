(function () {
    // IDs inyectados como data-* en la etiqueta <script> desde chat.php
    const scriptTag      = document.currentScript ||
                           document.querySelector('script[data-transaccion-id]');
    const TRANSACCION_ID = parseInt(scriptTag?.dataset?.transaccionId ?? 0);
    const CHAT_ID        = parseInt(scriptTag?.dataset?.chatId        ?? 0);

    function init() {
        const modalEl = document.getElementById('modalValoracion');
        if (!modalEl) return;

        // ── Estrellas interactivas ──────────────────────────────────────
        function initStarGroup(group) {
            const stars  = group.querySelectorAll('.star-btn');
            const field  = group.dataset.field;
            const hidden = document.getElementById('inp-' + field);
            let current  = 0;

            function paint(upTo) {
                stars.forEach((s, idx) => {
                    s.classList.toggle('bi-star-fill', idx < upTo);
                    s.classList.toggle('bi-star',      idx >= upTo);
                    s.style.color = idx < upTo ? '#ffc107' : '#ccc';
                });
            }

            stars.forEach((star, idx) => {
                star.addEventListener('mouseenter', () => paint(idx + 1));
                star.addEventListener('mouseleave', () => paint(current));
                star.addEventListener('click', () => {
                    current      = idx + 1;
                    hidden.value = current;
                    paint(current);
                    updateResumen();
                    checkReady();
                });
            });
        }

        // ── Resumen de media ────────────────────────────────────────────
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

        // ── Habilitar botón solo con los 3 criterios puntuados ─────────
        function checkReady() {
            const f = parseInt(document.getElementById('inp-fiabilidad').value)   || 0;
            const c = parseInt(document.getElementById('inp-comunicacion').value) || 0;
            const p = parseInt(document.getElementById('inp-puntualidad').value)  || 0;
            document.getElementById('btn-enviar-valoracion').disabled = !(f && c && p);
        }

        // ── Envío AJAX → chat.php (mismo archivo accesible) ────────────
        document.getElementById('btn-enviar-valoracion').addEventListener('click', function () {
            const btn          = this;
            const feedback     = document.getElementById('rating-feedback');
            const fiabilidad   = document.getElementById('inp-fiabilidad').value;
            const comunicacion = document.getElementById('inp-comunicacion').value;
            const puntualidad  = document.getElementById('inp-puntualidad').value;
            const comentario   = document.getElementById('comentario-val').value.trim();

            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';

            const body = new URLSearchParams({
                valoracion:     '1',
                transaccion_id: TRANSACCION_ID,
                fiabilidad,
                comunicacion,
                puntualidad,
                comentario
            });

            fetch(`/MercApp/public/views/chat.php?id=${CHAT_ID}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    body.toString()
            })
            .then(r => r.json())
            .then(data => {
                feedback.classList.remove('d-none', 'alert-danger', 'alert-success');
                if (data.ok) {
                    feedback.classList.add('alert', 'alert-success');
                    feedback.textContent = '¡Gracias! Tu valoración ha sido enviada.';
                    setTimeout(() => {
                        window.location.reload(); // ← cambia el hide() por esto
                    }, 1800);
                
                } else {
                    feedback.classList.add('alert', 'alert-danger');
                    feedback.textContent = data.error ?? 'Error al enviar la valoración.';
                    btn.disabled  = false;
                    btn.innerHTML = '<i class="bi bi-send me-1"></i> Enviar valoración';
                }
            })
            .catch(() => {
                feedback.classList.remove('d-none');
                feedback.classList.add('alert', 'alert-danger');
                feedback.textContent = 'Error de red. Inténtalo de nuevo.';
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i> Enviar valoración';
            });
        });

        // ── Inicializar grupos de estrellas ─────────────────────────────
        document.querySelectorAll('.star-group').forEach(initStarGroup);

        // ── Abrir el modal automáticamente ─────────────────────────────
        const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
        modal.show();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();