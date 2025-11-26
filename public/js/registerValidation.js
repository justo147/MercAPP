/* ===============================
   SELECTORES DEL FORMULARIO
   =============================== */
// Obtenemos referencias a los elementos del formulario
const formRegister = document.getElementById('formRegistro'); // formulario completo
const inputName = document.getElementById('name');           // input de nombre
const email = document.getElementById('email');             // input de correo
const pass1 = document.getElementById('password');          // input de contraseña
const pass2 = document.getElementById('confirmPass');       // input de confirmación de contraseña

/* ===============================
   EVENTO SUBMIT DEL FORMULARIO
   =============================== */
// Escuchamos el evento submit y evitamos el envío automático.
// En su lugar, llamamos a la función de validación.
formRegister.addEventListener('submit', function (e) {
    e.preventDefault(); // evita que el formulario se envíe antes de validar
    validationForm();   // llama a la función que valida todos los campos
});

/* ===============================
   FUNCIÓN PRINCIPAL DE VALIDACIÓN
   =============================== */
function validationForm() {
    let valid = true;   // variable que indica si todos los campos son correctos

    clearError();       // eliminamos mensajes y estilos de errores previos

    // Validación del nombre
    if (inputName.value.trim() === "") {
        showError(inputName, 'El nombre es obligatorio');
        valid = false;
    }

    // Validación del correo
    if (email.value.trim() === "") {
        showError(email, 'El correo es obligatorio');
        valid = false;
    }
    // Validación del correo patrones
    if (!validarEmail(email.value.trim())) {
        showError(email, 'Añade un email valido');
        valid = false;
    }

    // Validación de la contraseña
    if (pass1.value.trim() === "") {
        showError(pass1, 'La contraseña es obligatoria');
        valid = false;
    }

    // Validación de la confirmación de contraseña
    if (pass2.value.trim() === "") {
        showError(pass2, 'La contraseña es obligatoria');
        valid = false;
    }

    // Comprobación de que ambas contraseñas coinciden
    if (pass1.value != pass2.value) {
        showError(pass2, 'Las contraseñas tienen que ser iguales');
        valid = false;
    }

    // Si todos los campos son válidos, se puede enviar el formulario
    if (valid) {
        enviarFormulario();
    }
}


/* ==============================================
   ENVIO DE DATOS AL SERVIDOR DE FORMA ASINCRONA
   ============================================== */
async function enviarFormulario() {
     const formData = new FormData(formRegister); ;

    const res = await fetch("../views/register.php", {
        method: "POST",
        body: formData
    });

    const html = await res.text(); // recibimos HTML parcial
    document.getElementById("respuesta").innerHTML = html;

    // Si quieres redirigir en caso de éxito:
    if (html.includes("alert-success")) {
        setTimeout(() => {
            window.location.href = "pending_verification.php";
        }, 2000);
    }
}


/* ===============================
   FUNCIÓN DE VALIDACIÓN DE EMAIL
   =============================== */
/**
 * validarEmail
 * ------------
 * Verifica que el email ingresado tenga un formato válido.
 *
 * Parámetro:
 * - email: string con el valor del input de correo.
 *
 * Retorna:
 * - true si el email cumple el formato, false en caso contrario.
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/* ===============================
   FUNCIONES DE MANEJO DE ERRORES
   =============================== */
/**
 * showError
 * ----------
 * Muestra un mensaje de error debajo de un input específico y resalta visualmente el campo.
 *
 * Parámetros:
 * - input: el elemento <input> que tiene el error.
 * - message: el texto que se mostrará como mensaje de error.
 *
 * Funcionalidad:
 * 1. Añade la clase 'is-invalid' al input para cambiar su estilo .
 * 2. Comprueba si ya existe un mensaje de error en el contenedor del input:
 *    - Si existe, actualiza el texto.
 *    - Si no existe, crea un <p> con clase 'invalid-feedback' y lo agrega debajo del input.
 * 3. Esto evita que se creen mensajes duplicados cada vez que se valida el formulario.
 *
 * Uso: showError(inputName, "El nombre es obligatorio");
 */
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


/**
 * clearError
 * ----------
 * Elimina todos los mensajes de error y restaura el estilo normal de los inputs.
 *
 * Funcionalidad:
 * 1. Busca todos los elementos con la clase 'invalid-feedback' y los elimina del DOM.
 * 2. Busca todos los inputs con la clase 'is-invalid' y la elimina, quitando el borde rojo u otro estilo.
 *
 * Uso: clearError();
 * Esto se suele llamar al inicio de la validación de un formulario para limpiar errores previos.
 */
function clearError() {
    // elimina todos los mensajes de error de Bootstrap
    document.querySelectorAll('.invalid-feedback').forEach(e => e.remove());

    // quita la clase de borde rojo de los inputs
    document.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
}

