/**
 * Inicializa el comportamiento interactivo del bloque de preguntas frecuentes (FAQ).
 * Selecciona todos los elementos con la clase `.faq-question` y les asigna un evento de clic
 * que alterna la visibilidad de su contenedor padre (`.faq-item`).
 */
document.querySelectorAll('.faq-question').forEach(question => {
  /**
   * Asigna un evento de clic a cada pregunta.
   * Al hacer clic, se alterna la clase "open" en el contenedor padre para mostrar u ocultar el contenido.
   */
  question.addEventListener('click', () => {
    /** @type {HTMLElement} Contenedor padre del elemento clicado */
    const item = question.parentElement;

    // Alterna la clase "open" para abrir/cerrar el bloque
    item.classList.toggle('open');
  });
});
