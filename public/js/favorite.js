/**
 * Alterna entre añadir y eliminar un producto de favoritos.
 */
function toggleFavorito(productoId) {

    fetch(`${BASE}/api/is_favorite.php?producto_id=${productoId}`)
        .then(r => r.text())
        .then(esFav => {

            if (esFav == "1") {
                // Eliminar de favoritos
                fetch(`${BASE}/api/remove_favorite.php`, {
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
                fetch(`${BASE}/api/add_favorite.php`, {
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
    const favBtn     = document.getElementById("favBtn");
    const productoId = window.productoIdGlobal;

    if (!favBtn || !productoId) return;

    fetch(`${BASE}/api/is_favorite.php?producto_id=${productoId}`)
        .then(r => r.text())
        .then(esFav => {
            favBtn.innerHTML = esFav == "1"
                ? '<i class="bi bi-heart-fill"></i>'
                : '<i class="bi bi-heart"></i>';
        });
});
