/**
 * Botón que alterna el tema claro/oscuro.
 * @type {HTMLButtonElement}
 */
const toggleBtn = document.getElementById('themeToggle');

/**
 * Referencia al elemento <body> para aplicar clases de tema.
 * @type {HTMLBodyElement}
 */
const body = document.body;

/**
 * Intentamos leer el tema guardado en localStorage.
 * Uso de try...catch porque:
 * - localStorage puede estar deshabilitado por el navegador.
 * - El usuario puede tener bloqueado el almacenamiento.
 * - El valor guardado podría estar corrupto o inaccesible.
 */
try {
  const savedTheme = localStorage.getItem('theme');

  if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
    toggleBtn.textContent = '☀️';
  }
} catch (error) {
  console.error("Error al leer localStorage:", error);
  // No aplicamos tema guardado, pero la app sigue funcionando.
}

/**
 * Evento para alternar el tema.
 */
toggleBtn.addEventListener('click', () => {
  const darkModeEnabled = body.classList.toggle('dark-mode');
  toggleBtn.textContent = darkModeEnabled ? '☀️' : '🌙';

  /**
   * Guardamos la preferencia del usuario.
   * try...catch necesario porque:
   * - localStorage.setItem puede lanzar excepciones si:
   *   - El almacenamiento está lleno.
   *   - El usuario está en modo incógnito (Safari).
   *   - El navegador bloquea el acceso por políticas de seguridad.
   */
  try {
    localStorage.setItem('theme', darkModeEnabled ? 'dark' : 'light');
  } catch (error) {
    console.error("No se pudo guardar la preferencia de tema:", error);
    // La app sigue funcionando aunque no se guarde la preferencia.
  }
});
