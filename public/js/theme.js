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

// Aplicar tema guardado al cargar la página
if (localStorage.getItem('theme') === 'dark') {
  body.classList.add('dark-mode'); // Activa modo oscuro
  toggleBtn.textContent = '☀️';    // Cambia icono a sol
}

// Cambiar tema al hacer clic en el botón
toggleBtn.addEventListener('click', () => {
  // Alterna la clase dark-mode en el body
  const darkModeEnabled = body.classList.toggle('dark-mode');

  // Cambia el icono según el tema activo
  toggleBtn.textContent = darkModeEnabled ? '☀️' : '🌙';

  // Guarda la preferencia en localStorage
  localStorage.setItem('theme', darkModeEnabled ? 'dark' : 'light');
});
