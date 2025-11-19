/**
 * Obtiene la información del usuario almacenada en el localStorage.
 * @type {{nombre: string, email: string} | null}
 */
const usuarioLogueado = JSON.parse(localStorage.getItem('usuarioLogueado'));

/**
 * Elemento HTML donde se mostrará el nombre del usuario.
 * @type {HTMLElement | null}
 */
const userNameElement = document.getElementById('userName');

/**
 * Elemento HTML donde se mostrará el email del usuario.
 * @type {HTMLElement | null}
 */
const userEmailElement = document.getElementById('userEmail');

/**
 * Muestra los datos del usuario en la página si está logueado.
 * Si no hay usuario logueado, redirige al login.
 */
if (usuarioLogueado) {
    // Mostrar los datos en la página
    userNameElement.textContent = usuarioLogueado.nombre;
    userEmailElement.textContent = usuarioLogueado.email;
    document.getElementById('welcomeMessage').textContent = `¡Bienvenido, ${usuarioLogueado.nombre}!`;
} else {
    // Si no hay usuario logueado, redirigir al login
    alert('No has iniciado sesión');
    window.location.href = 'login.html';
}

/**
 * Cierra la sesión del usuario eliminando su información del localStorage.
 * Muestra una alerta de confirmación.
 */
function cerrarSesion() {
    localStorage.removeItem('usuarioLogueado');
    alert('Sesión cerrada');
}
