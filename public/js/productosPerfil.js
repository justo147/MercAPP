document.addEventListener("DOMContentLoaded", () => {
  const contenedor = document.getElementById("productos-usuario");

  if (!contenedor) return;

  // Obtener el ID del usuario desde la URL
  const params = new URLSearchParams(window.location.search);
  const userId = params.get("id");

  // Si no hay ID, mostramos mensaje y salimos
  if (!userId) {
    contenedor.innerHTML = `
      <div class="col-12">
        <p class="text-muted">No se ha proporcionado un ID de usuario.</p>
      </div>`;
    return;
  }

  // Llamada a la API con el ID
  fetch(`../../api/productos_usuario.php?id=${userId}`)
    .then(async res => {
      try {
        return await res.json();
      } catch (e) {
        console.error("La API no devolvió JSON válido:", e);
        return { error: "invalid_json" };
      }
    })
    .then(productos => {

      if (productos.error) {
        contenedor.innerHTML = `
          <div class="col-12">
            <p class="text-muted">${productos.error}</p>
          </div>`;
        return;
      }

      if (!Array.isArray(productos) || productos.length === 0) {
        contenedor.innerHTML = `
          <div class="col-12">
            <p class="text-muted">No tienes productos publicados.</p>
          </div>`;
        return;
      }

      productos.forEach(prod => {
        const col = document.createElement("div");
        col.className = "col-12 col-md-6 col-lg-4";

        const imagenPrincipal =
          prod.imagenes && prod.imagenes.length > 0
            ? prod.imagenes[0].url
            : "/uploads/products/default-product.png";

        col.innerHTML = `
          <div class="card h-100 no-hover">
            <img src="../../${imagenPrincipal}" class="card-img-top" alt="${prod.titulo}">
            <div class="card-body">
              <h5 class="card-title">${prod.titulo}</h5>
              <p class="card-text text-muted mb-2">${prod.descripcion || "Sin descripción"}</p>
              <p><i class="bi bi-cash"></i> <strong>Precio:</strong> ${prod.precio} €</p>
              <p><i class="bi bi-tag"></i> <strong>Categoría:</strong> ${prod.categoria}</p>
              <p><i class="bi bi-box"></i> <strong>Estado:</strong> ${prod.estado_producto}</p>
              <p><i class="bi bi-arrow-left-right"></i> <strong>Transacción:</strong> ${prod.tipo_transaccion}</p>
              <p><i class="bi bi-geo-alt"></i> <strong>Ubicación:</strong> ${prod.ubicacion || "No indicada"}</p>
              <p class="text-muted"><i class="bi bi-calendar"></i> <small>${prod.fecha_publicacion}</small></p>
            </div>
          </div>
        `;

        contenedor.appendChild(col);
      });
    })
    .catch(err => {
      console.error("Error cargando productos:", err);
      contenedor.innerHTML = `
        <div class="col-12">
          <p class="text-muted">Error cargando productos.</p>
        </div>`;
    });
});
