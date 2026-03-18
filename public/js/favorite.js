/**
 * Alterna entre añadir y eliminar un producto de favoritos.
 */
function toggleFavorito(productoId) {

    fetch(`/MercApp/controllers/handlers/is_favorite_handle.php?producto_id=${productoId}`)
        .then(r => r.text())
        .then(esFav => {

            if (esFav == "1") {
                // Eliminar de favoritos
                fetch("/MercApp/controllers/handlers/remove_favorite_handle.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `producto_id=${productoId}`
                }).then(() => {
                    document.getElementById("favBtn").innerHTML =
                        '<i class="bi bi-heart"></i>';
                });

            } else {
                // Añadir a favoritos
                fetch("/MercApp/controllers/handlers/add_favorite_handle.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `producto_id=${productoId}`
                }).then(() => {
                    document.getElementById("favBtn").innerHTML =
                        '<i class="bi bi-heart-fill"></i>';
                });
            }
        });
}

/**
 * Al cargar la página, se consulta si el producto ya está en favoritos
 * para mostrar el icono correcto.
 */
document.addEventListener("DOMContentLoaded", () => {
    const productoId = window.productoIdGlobal;

    fetch(`/MercApp/controllers/handlers/is_favorite_handle.php?producto_id=${productoId}`)
        .then(r => r.text())
        .then(esFav => {
            document.getElementById("favBtn").innerHTML =
                esFav == "1"
                    ? '<i class="bi bi-heart-fill"></i>'
                    : '<i class="bi bi-heart"></i>';
        });
});
