/* ===============================
   SELECTORES DEL FORMULARIO
   =============================== */

/**
 * Formulario de inicio de sesión.
 * @type {HTMLFormElement}
 */
const formLogin = document.getElementById('formLogin');

/**
 * Campo de entrada para el correo electrónico.
 * @type {HTMLInputElement}
 */
const inputEmail = document.getElementById('email');

/**
 * Campo de entrada para la contraseña.
 * @type {HTMLInputElement}
 */
const inputPass = document.getElementById('password');


/* ===============================
   EVENTO SUBMIT DEL FORMULARIO
   =============================== */

/**
 * Evento que intercepta el envío del formulario para validar los datos antes de enviarlo.
 */
formLogin.addEventListener('submit', function(e) {
    e.preventDefault();
    validateLogin();
});


/* ===============================
   FUNCIÓN PRINCIPAL DE VALIDACIÓN
   =============================== */

/**
 * Valida los campos del formulario de login.
 * Comprueba correo y contraseña, muestra errores y envía el formulario si todo es válido.
 */
function validateLogin() {
    let valid = true;

    clearError();

    // Validación de correo
    if (inputEmail.value.trim() === "") {
        showError(inputEmail, 'El correo es obligatorio');
        valid = false;
    } else if (!validarEmail(inputEmail.value.trim())) {
        showError(inputEmail, 'Añade un email válido');
        valid = false;
    }

    // Validación de contraseña
    if (inputPass.value.trim() === "") {
        showError(inputPass, 'La contraseña es obligatoria');
        valid = false;
    }

    // Si todo es válido, enviar formulario
    if (valid) {
        console.log("Formulario de login válido");
        formLogin.submit();
    }
}


/* ===============================
   FUNCIÓN DE VALIDACIÓN DE EMAIL
   =============================== */

/**
 * Valida si un correo electrónico tiene un formato correcto.
 * @param {string} email - Correo electrónico a validar.
 * @returns {boolean} `true` si el email es válido, `false` en caso contrario.
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}


/* ===============================
   FUNCIONES DE MANEJO DE ERRORES
   =============================== */

/**
 * Muestra un mensaje de error debajo de un input y aplica estilos de Bootstrap.
 * @param {HTMLInputElement} input - Campo de formulario donde mostrar el error.
 * @param {string} message - Mensaje de error a mostrar.
 */
function showError(input, message) {
    if (!input) return; // seguridad por si se pasa un input nulo

    // Añade la clase de Bootstrap para marcar el input como inválido
    input.classList.add('is-invalid');

    const parent = input.parentElement;
    let existing = parent.querySelector('.invalid-feedback');

    if (existing) {
        existing.textContent = message;
    } else {
        const div = document.createElement('div');
        div.className = 'invalid-feedback';
        div.textContent = message;
        parent.appendChild(div);
    }
}

/**
 * Limpia todos los mensajes de error y estilos de validación del formulario.
 */
function clearError() {
    // elimina todos los mensajes de error de Bootstrap
    document.querySelectorAll('.invalid-feedback').forEach(e => e.remove());

    // quita la clase de borde rojo de los inputs
    document.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
}
