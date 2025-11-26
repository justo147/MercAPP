/* ===============================
   SELECTORES DEL FORMULARIO
   =============================== */
const formLogin = document.getElementById('formLogin'); // formulario de login
const inputEmail = document.getElementById('email');    // input de correo
const inputPass = document.getElementById('password');  // input de contraseña

/* ===============================
   EVENTO SUBMIT DEL FORMULARIO
   =============================== */
formLogin.addEventListener('submit', function(e) {
    e.preventDefault(); // evita envío automático
    validateLogin();
});

/* ===============================
   FUNCIÓN PRINCIPAL DE VALIDACIÓN
   =============================== */
function validateLogin() {
    let valid = true;

    clearError(); // limpia errores previos

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
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/* ===============================
   FUNCIONES DE MANEJO DE ERRORES
   =============================== */
function showError(input, message) {
    if (!input) return; // seguridad por si se pasa un input nulo

    // Añade la clase de Bootstrap para marcar el input como inválido
    input.classList.add('is-invalid');

    const parent = input.parentElement; // contenedor del input
    let existing = parent.querySelector('.invalid-feedback'); // busca si ya hay mensaje

    if (existing) {
        existing.textContent = message; // actualiza mensaje existente
    } else {
        const div = document.createElement('div'); // crea un nuevo contenedor
        div.className = 'invalid-feedback';        // clase Bootstrap para feedback
        div.textContent = message;                 // agrega el mensaje
        parent.appendChild(div);                   // lo inserta debajo del input
    }
}

function clearError() {
    // elimina todos los mensajes de error de Bootstrap
    document.querySelectorAll('.invalid-feedback').forEach(e => e.remove());

    // quita la clase de borde rojo de los inputs
    document.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
}
