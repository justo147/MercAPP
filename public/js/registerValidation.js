/* ============================================================
   SELECTORES DEL FORMULARIO
============================================================ */

/**
 * Formulario de registro de usuario.
 * @type {HTMLFormElement}
 */
const formRegister = document.getElementById('formRegistro');

/**
 * Campo de entrada para el nombre del usuario.
 * @type {HTMLInputElement}
 */
const inputName = document.getElementById('name');

/**
 * Campo de entrada para el correo electrónico.
 * @type {HTMLInputElement}
 */
const emailInput = document.getElementById('email');

/**
 * Campo de entrada para la contraseña.
 * @type {HTMLInputElement}
 */
const pass1 = document.getElementById('password');

/**
 * Campo de entrada para confirmar la contraseña.
 * @type {HTMLInputElement}
 */
const pass2 = document.getElementById('confirmPass');


/* ============================================================
   EVENTO SUBMIT
============================================================ */

/**
 * Intercepta el envío del formulario para validar los datos antes de enviarlos.
 */
formRegister.addEventListener('submit', function (e) {
    e.preventDefault();
    validationForm();
});


/* ============================================================
   FUNCIÓN PRINCIPAL DE VALIDACIÓN
============================================================ */

/**
 * Valida todos los campos del formulario de registro.
 * Comprueba nombre, email, contraseñas y muestra errores si es necesario.
 * Si todo es válido, procede a enviar el formulario.
 */
function validationForm() {
    let valid = true;
    clearError();

    if (inputName.value.trim() === "") {
        showError(inputName, 'El nombre es obligatorio');
        valid = false;
    }

    if (emailInput.value.trim() === "") {
        showError(emailInput, 'El correo es obligatorio');
        valid = false;
    } else if (!validarEmail(emailInput.value.trim())) {
        showError(emailInput, 'Añade un email válido');
        valid = false;
    }

    if (pass1.value.trim() === "") {
        showError(pass1, 'La contraseña es obligatoria');
        valid = false;
    }

    if (pass2.value.trim() === "") {
        showError(pass2, 'Confirma tu contraseña');
        valid = false;
    } else if (pass1.value !== pass2.value) {
        showError(pass2, 'Las contraseñas tienen que ser iguales');
        valid = false;
    }

    if (valid) {
        enviarFormulario();
    }
}


/* ============================================================
   ENVÍO ASÍNCRONO Y LÓGICA DEL BOM
============================================================ */

/**
 * Envía el formulario de registro mediante fetch de forma asíncrona.
 * Gestiona la respuesta del servidor, limpia mensajes previos
 * y muestra un modal de éxito usando características del BOM.
 * @async
 */
async function enviarFormulario() {
    const formData = new FormData(formRegister);
    const emailUser = emailInput.value.trim();
    const contenedorRespuesta = document.getElementById("respuesta");
    const btnSubmit = formRegister.querySelector('[type="submit"]');

    // Loading state
    const origHtml = btnSubmit?.innerHTML;
    if (btnSubmit) {
        btnSubmit.disabled  = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando…';
    }

    try {
        const res = await fetch("register.php", {
            method: "POST",
            body: formData
        });

        const respuestaRaw = await res.text();
        const respuesta = respuestaRaw.replace(/\s+/g, '');

        if (respuesta.includes("REGISTRO_EXITOSO")) {
            contenedorRespuesta.innerHTML     = "";
            contenedorRespuesta.style.display = "none";
            abrirModalExito(emailUser);
        } else {
            contenedorRespuesta.style.display = "block";
            contenedorRespuesta.innerHTML     = respuestaRaw;
            if (btnSubmit) {
                btnSubmit.disabled  = false;
                btnSubmit.innerHTML = origHtml;
            }
        }
    } catch (error) {
        console.error("Error:", error);
        contenedorRespuesta.style.display = "block";
        contenedorRespuesta.innerHTML = '<p class="text-danger small">Error de conexión. Inténtalo de nuevo.</p>';
        if (btnSubmit) {
            btnSubmit.disabled  = false;
            btnSubmit.innerHTML = origHtml;
        }
    }
}


/**
 * Muestra un modal de éxito tras el registro.
 * Utiliza propiedades del BOM como scroll, viewport y pushState.
 * @param {string} correo - Correo del usuario registrado.
 */
function abrirModalExito(correo) {
    const modal = document.getElementById('modalSuccess');
    const overlay = document.getElementById('modalOverlay');
    const spanEmail = document.getElementById('userEmail');
    
    if (modal && overlay) {
        spanEmail.textContent = correo;

        // 1. BOM: window.scrollY y window.innerHeight 
        // Posicionamiento dinámico para que no importe cuánto scroll haya hecho el usuario
        const posicionY = window.scrollY + (window.innerHeight / 5);
        
        modal.style.top = posicionY + "px";
        modal.style.display = "block";
        overlay.style.display = "block";

        // 2. BOM: window.history 
        // Actualizamos la URL sin refrescar
        window.history.pushState({ etapa: "finalizado" }, "Registro Exitoso", "?registro=ok");
        
        console.log("BOM: Modal mostrada en viewport alto " + window.innerHeight);
    } else {
        // Fallback si el HTML no tiene los IDs correctos
        const contenedorRespuesta = document.getElementById("respuesta");
        if (contenedorRespuesta) {
            contenedorRespuesta.style.display = "block";
            contenedorRespuesta.innerHTML = `<p class="text-success">¡Registro correcto! Revisa tu email: ${correo}</p>`;
        }
    }
}


/* ============================================================
   FUNCIONES AUXILIARES
============================================================ */

/**
 * Valida si un correo electrónico tiene un formato correcto.
 * @param {string} correo - Correo a validar.
 * @returns {boolean} `true` si el correo es válido.
 */
function validarEmail(correo) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
}

/**
 * Muestra un mensaje de error debajo de un input y aplica estilos de Bootstrap.
 * @param {HTMLInputElement} input - Campo donde mostrar el error.
 * @param {string} message - Mensaje de error.
 */
function showError(input, message) {
    input.classList.add('is-invalid');
    const parent = input.parentElement;
    let existing = parent.querySelector('.invalid-feedback');

    if (!existing) {
        const div = document.createElement('div');
        div.className = 'invalid-feedback';
        div.textContent = message;
        parent.appendChild(div);
    } else {
        existing.textContent = message;
    }
}

/**
 * Limpia todos los mensajes de error y estilos de validación del formulario.
 */
function clearError() {
    document.querySelectorAll('.invalid-feedback').forEach(e => e.remove());
    document.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
}


/* ============================================================
   TOGGLE VISIBILIDAD DE CONTRASEÑA
============================================================ */

function setupPasswordToggle(btnId, inputId, iconId) {
    const btn   = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!btn || !input) return;
    btn.addEventListener('click', () => {
        const visible = input.type === 'text';
        input.type    = visible ? 'password' : 'text';
        icon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
        btn.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    });
}

setupPasswordToggle('toggle-pass',    'password',    'icon-pass');
setupPasswordToggle('toggle-confirm', 'confirmPass', 'icon-confirm');


/* ============================================================
   INDICADOR DE FUERZA DE CONTRASEÑA
============================================================ */

function calcularFuerza(pwd) {
    let score = 0;
    if (pwd.length >= 8)                    score++;
    if (pwd.length >= 12)                   score++;
    if (/[A-Z]/.test(pwd))                  score++;
    if (/[0-9]/.test(pwd))                  score++;
    if (/[^A-Za-z0-9]/.test(pwd))          score++;
    return score; // 0-5
}

pass1.addEventListener('input', () => {
    const pwd   = pass1.value;
    const wrap  = document.getElementById('strength-wrap');
    const bar   = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');

    if (!pwd) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';

    const score = calcularFuerza(pwd);
    const niveles = [
        { pct: 20,  cls: 'bg-danger',  txt: 'Muy débil'  },
        { pct: 40,  cls: 'bg-danger',  txt: 'Débil'      },
        { pct: 60,  cls: 'bg-warning', txt: 'Regular'    },
        { pct: 80,  cls: 'bg-info',    txt: 'Buena'      },
        { pct: 100, cls: 'bg-success', txt: 'Muy fuerte' },
    ];
    const nivel = niveles[Math.min(score, 4)];
    bar.style.width = nivel.pct + '%';
    bar.className   = 'progress-bar ' + nivel.cls;
    label.textContent = nivel.txt;
    label.className   = 'small text-muted';
});


/* ============================================================
   VALIDACIÓN EN TIEMPO REAL DE CONFIRMACIÓN
============================================================ */

pass2.addEventListener('input', () => {
    const feedback = document.getElementById('confirm-feedback');
    if (!feedback) return;
    if (!pass2.value) { feedback.style.display = 'none'; return; }
    feedback.style.display = 'block';
    if (pass1.value === pass2.value) {
        feedback.textContent  = '✓ Las contraseñas coinciden';
        feedback.className    = 'small mt-1 text-success';
    } else {
        feedback.textContent  = '✗ Las contraseñas no coinciden';
        feedback.className    = 'small mt-1 text-danger';
    }
});
