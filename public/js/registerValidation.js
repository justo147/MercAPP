/* ============================================================
   SELECTORES DEL FORMULARIO
   ============================================================ */
const formRegister = document.getElementById('formRegistro');
const inputName = document.getElementById('name');
const emailInput = document.getElementById('email');
const pass1 = document.getElementById('password');
const pass2 = document.getElementById('confirmPass');

/* ============================================================
   EVENTO SUBMIT
   ============================================================ */
formRegister.addEventListener('submit', function (e) {
    e.preventDefault();
    validationForm();
});

/* ============================================================
   FUNCIÓN PRINCIPAL DE VALIDACIÓN
   ============================================================ */
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
async function enviarFormulario() {
    const formData = new FormData(formRegister);
    const emailUser = emailInput.value.trim();
    const contenedorRespuesta = document.getElementById("respuesta");

    try {
        const res = await fetch("register.php", {
            method: "POST",
            body: formData
        });

        const respuestaRaw = await res.text();
        // Usamos una expresión regular para limpiar CUALQUIER espacio, salto de línea o tabulación
        const respuesta = respuestaRaw.replace(/\s+/g, ''); 

        console.log("Respuesta procesada:", respuesta);

        if (respuesta.includes("REGISTRO_EXITOSO")) {
            // 1. Limpieza total inmediata del div que ves en tus capturas
            contenedorRespuesta.innerHTML = "";
            contenedorRespuesta.style.display = "none";
            
            // 2. Ejecutar Modal (BOM)
            abrirModalExito(emailUser);
        } else {
            // Solo si hay error mostramos el texto
            contenedorRespuesta.style.display = "block";
            contenedorRespuesta.innerHTML = respuestaRaw;
        }
    } catch (error) {
        console.error("Error:", error);
    }
}

/**
 * abrirModalExito: Requerimientos del BOM
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
        // Alerta de respaldo si el HTML no tiene los IDs correctos
        alert("¡Registro correcto! Revisa tu email: " + correo);
    }
}

/* ============================================================
   FUNCIONES AUXILIARES
   ============================================================ */
function validarEmail(correo) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
}

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

function clearError() {
    document.querySelectorAll('.invalid-feedback').forEach(e => e.remove());
    document.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
}