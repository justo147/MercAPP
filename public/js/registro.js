/**
 * Array que contiene los usuarios registrados.
 * Se obtiene desde localStorage o se inicializa como vacío.
 * @type {Array<{nombre: string, email: string, password: string}>}
 */
let usuarios = JSON.parse(localStorage.getItem('usuarios')) || [];

/**
 * Maneja el evento de envío del formulario de registro.
 * Crea un nuevo usuario, verifica si el correo ya está registrado,
 * y si no lo está, lo guarda en localStorage y redirige al login.
 *
 * @param {SubmitEvent} e - Evento de envío del formulario.
 */
document.getElementById('formRegistro').addEventListener('submit', function (e) {
  e.preventDefault();

  /**
   * Datos del formulario enviados por el usuario.
   * @type {FormData}
   */
  const formData = new FormData(this);

  /**
   * Objeto que representa al nuevo usuario.
   * @type {{nombre: string, email: string, password: string}}
   */
  const nuevoUsuario = {
    nombre: formData.get('name'),
    email: formData.get('email'),
    password: formData.get('password')
  };

  /**
   * Verifica si el correo electrónico ya está registrado.
   * @type {boolean}
   */
  const existe = usuarios.some(user => user.email === nuevoUsuario.email);

  if (existe) {
    alert('Este correo ya está registrado. Intenta con otro.');
    return;
  }

  // Guardar el nuevo usuario en el array y en localStorage
  usuarios.push(nuevoUsuario);
  localStorage.setItem('usuarios', JSON.stringify(usuarios));

  alert('¡Registro exitoso!');
  window.location.href = 'login.php'; // Redirige al login
});
