document.addEventListener("DOMContentLoaded", () => {

  const contenedor = document.getElementById("productos-usuario");
  const paginacion = document.getElementById("paginacion-productos");
  const buscador = document.getElementById("buscador-productos");

  if (!contenedor) return;

  const userId = PERFIL_ID;

  let page = 1;
  const limit = 6;
  let searchQueryPerfil = "";

  // ============================
  // CARGAR PRODUCTOS
  // ============================
  function cargarProductos() {

    const url = new URL("/MercApp/api/productos_usuario.php", window.location.origin);
    url.searchParams.set("id", userId);
    url.searchParams.set("page", page);
    url.searchParams.set("limit", limit);

    if (searchQueryPerfil) {
      url.searchParams.set("q", searchQueryPerfil);
    }

    fetch(url)
      .then(res => res.json())
      .then(data => {

        if (!data.success) {
          contenedor.innerHTML = `<p class="text-danger">${data.error || "Error cargando productos"}</p>`;
          paginacion.innerHTML = "";
          return;
        }

        const productos = data.productos || [];
        const total = data.total || 0;

        contenedor.innerHTML = "";

        if (productos.length === 0) {
          contenedor.innerHTML = `
            <div class="col-12">
              <p class="text-muted">No se encontraron productos.</p>
            </div>`;
          paginacion.innerHTML = "";
          return;
        }

        productos.forEach(prod => {
          const col = document.createElement("div");
          col.className = "col-12 col-md-6 col-lg-4 fade-in";

          const imagenes = prod.imagenes?.length
            ? prod.imagenes
            : [{ url: "uploads/products/default.jpg" }];

          const idCarrusel = "carousel_" + prod.id;

          col.innerHTML = `
            <div class="card h-100 border rounded-3 shadow-sm">

              <div id="${idCarrusel}" class="carousel carousel-dark slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                  ${imagenes.map((img, i) => `
                    <div class="carousel-item ${i === 0 ? "active" : ""}">
                      <img src="/MercApp/${img.url}" class="d-block w-100"
                        alt="Imagen ${i + 1} de ${prod.titulo}"
                        style="height: 200px; object-fit: cover; border-bottom: 1px solid #ddd;">
                    </div>
                  `).join("")}
                </div>

                ${imagenes.length > 1 ? `
                  <button class="carousel-control-prev" type="button" data-bs-target="#${idCarrusel}" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                  </button>
                  <button class="carousel-control-next" type="button" data-bs-target="#${idCarrusel}" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                  </button>
                ` : ""}
              </div>

              <div class="card-body">
                <h2 class="card-title fw-semibold mb-2">${prod.titulo}</h2>

                <div class="d-flex align-items-center mb-2">
                  <span class="badge bg-success me-2">€${prod.precio}</span>
                  <span class="badge bg-primary">${prod.estado_producto}</span>
                </div>

                <div class="small text-muted mb-2">
                  <i class="bi bi-tag"></i> ${prod.categoria}
                </div>

                <div class="small text-muted mb-2">
                  <i class="bi bi-geo-alt"></i> ${prod.ubicacion || "No indicada"}
                </div>

                <div class="small text-muted">
                  <i class="bi bi-calendar"></i> ${prod.fecha_publicacion}
                </div>
              </div>

              <div class="card-footer border-0">
                ${ES_PROPIETARIO ? `
                  <div class="d-flex justify-content-between">
                    <a href="mod_product.php?id=${prod.id}" class="btn btn-sm btn-warning">Editar</a>
                    <button 
                          class="btn btn-sm btn-outline-danger"
                          data-bs-toggle="modal"
                          data-bs-target="#modalEliminarProducto"
                          data-product-id="${prod.id}">
                          Eliminar
                    </button>

                  </div>
                ` : `
                  <a href="mensaje.php?to=${prod.usuario_id}" class="btn btn-sm btn-primary w-100">Contactar</a>
                `}
              </div>

            </div>
          `;

          contenedor.appendChild(col);

          // Activar animación suave
          requestAnimationFrame(() => col.classList.add("show"));
        });

        // ============================
        // PAGINACIÓN
        // ============================
        const totalPages = Math.ceil(total / limit);

        paginacion.innerHTML = `
<nav class="mt-4">
  <ul class="pagination justify-content-center">

    <li class="page-item ${page <= 1 ? "disabled" : ""}">
      <button class="page-link text-primary border-primary" id="btn-prev">
        <i class="bi bi-chevron-left"></i> Anterior
      </button>
    </li>

    <li class="page-item disabled">
      <span class="page-link fw-semibold bg-primary text-white">
        Página ${page} de ${totalPages}
      </span>
    </li>

    <li class="page-item ${page >= totalPages ? "disabled" : ""}">
      <button class="page-link text-primary border-primary" id="btn-next">
        Siguiente <i class="bi bi-chevron-right"></i>
      </button>
    </li>

  </ul>
</nav>
`;

        document.getElementById("btn-prev")?.addEventListener("click", () => {
          if (page > 1) {
            page--;
            cargarProductos();
          }
        });

        document.getElementById("btn-next")?.addEventListener("click", () => {
          if (page < totalPages) {
            page++;
            cargarProductos();
          }
        });

      })
      .catch(err => console.error("Error cargando productos:", err));
  }

  cargarProductos();

  // ============================
  // BUSCADOR
  // ============================
  buscador?.addEventListener("input", e => {
    searchQueryPerfil = e.target.value.trim();
    page = 1;
    cargarProductos();
  });

  // ============================
  // ESTADÍSTICAS DEL USUARIO
  // ============================
  fetch(`/MercApp/api/stats.php?id=${PERFIL_ID}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;

      const stats = data.data;

      document.getElementById("stat-productos").textContent = stats.productos;
      document.getElementById("stat-ventas").textContent = stats.ventas;
      document.getElementById("stat-valoracion").textContent = stats.valoracion;
    })
    .catch(err => console.error("Error cargando estadísticas:", err));

});
