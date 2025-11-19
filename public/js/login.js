/**
 * Maneja el evento de envío del formulario de login.
 * Valida las credenciales del usuario contra los datos almacenados en localStorage.
 * Si son correctas, guarda el usuario logueado y redirige a la página principal.
 * Si son incorrectas, muestra una alerta de error.
 *
 * @param {SubmitEvent} e - Evento de envío del formulario.
 */
document.getElementById('formLogin').addEventListener('submit', function (e) {
    e.preventDefault();

    /**
     * Datos del formulario enviados por el usuario.
     * @type {FormData}
     */
    const formData = new FormData(this);

    /**
     * Email ingresado por el usuario.
     * @type {string}
     */
    const email = formData.get('email');

    /**
     * Contraseña ingresada por el usuario.
     * @type {string}
     */
    const password = formData.get('password');

    /**
     * Lista de usuarios registrados almacenados en localStorage.
     * @type {Array<{email: string, password: string, nombre: string}>}
     */
    const usuarios = JSON.parse(localStorage.getItem('usuarios')) || [];

    /**
     * Usuario encontrado que coincide con las credenciales ingresadas.
     * @type {{email: string, password: string, nombre: string} | undefined}
     */
    const usuarioEncontrado = usuarios.find(user =>
        user.email === email && user.password === password
    );

    if (usuarioEncontrado) {
        // Guardar el usuario logueado en localStorage
        localStorage.setItem('usuarioLogueado', JSON.stringify(usuarioEncontrado));

        alert('Login exitoso!');
        window.location.href = 'home.html';
    } else {
        alert('Email o contraseña incorrectos');
    }
});
