// Selecciona todas las preguntas del FAQ
document.querySelectorAll('.faq-question').forEach(question => {

  // Añade un evento de clic a cada pregunta
  question.addEventListener('click', () => {

    // Obtiene el contenedor padre (.faq-item)
    const item = question.parentElement;

    // Alterna la clase "open" para abrir/cerrar el bloque
    item.classList.toggle('open');
  });
});
