/**
 * Description placeholder
 *
 * @type {*}
 */
const toggleBtn = document.getElementById('themeToggle');
/**
 * Description placeholder
 *
 * @type {*}
 */
const body = document.body;

// Aplicar tema guardado al cargar la página
if (localStorage.getItem('theme') === 'dark') {
  body.classList.add('dark-mode');
  toggleBtn.textContent = '☀️';
}

// Cambiar tema al hacer clic
toggleBtn.addEventListener('click', () => {
  const darkModeEnabled = body.classList.toggle('dark-mode');
  toggleBtn.textContent = darkModeEnabled ? '☀️' : '🌙';
  localStorage.setItem('theme', darkModeEnabled ? 'dark' : 'light');
});


