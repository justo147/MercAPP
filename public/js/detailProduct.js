// Función global que mueve el carrusel de productos sugeridos
// direction = -1 para mover a la izquierda, 1 para mover a la derecha
window.scrollCarousel = function(direction) {

    // Obtenemos el elemento del carrusel por su ID
    const carousel = document.getElementById("carousel");

    // Si por alguna razón no existe, salimos para evitar errores
    if (!carousel) return;

    // Cantidad de píxeles que se desplazará el carrusel por cada clic
    const amount = 250;

    // Realizamos el desplazamiento horizontal con animación suave
    carousel.scrollBy({
        left: direction * amount, // Multiplicamos por -1 o 1 según la flecha pulsada
        behavior: "smooth"        // Movimiento suave
    });
};

