<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';
if (!isset($_SESSION["user_id"])) {
  header("Location: ../views/auth/login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MercApp - Productos de tus seguidores</title>

  <!-- Favicon -->
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/homeStyle.css">


  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

  <!-- Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- JS -->

  <link href="../css/style-guide.css" rel="stylesheet">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container mt-4">
    <h2 class="mb-4">
        <i class="bi bi-people-fill"></i> Productos de usuarios que sigues
    </h2>

    <div class="row" id="product-list"></div>

    <div class="text-center mt-4">
        <button id="loadMore" class="btn btn-primary">Cargar más</button>
    </div>
</div>


<script>
let page = 1;

function renderProducts(products) {
    const container = document.getElementById("product-list");

    products.forEach(p => {
        const img = p.imagenes?.length
            ? `/MercApp/${p.imagenes[0].url}`
            : "/MercApp/uploads/products/default.jpg";

        const col = document.createElement("div");
        col.classList.add("col-6", "col-md-4", "col-lg-3", "mb-4", "sinFondo", "fade-in");

        col.innerHTML = `
            <div class="card h-100 shadow-sm border">
                <img src="${img}" class="card-img-top" alt="Imagen de ${p.titulo}" style="height: 180px; object-fit: cover;">

                <div class="card-body d-flex flex-column">
                    <h2 class="card-title text-truncate small" title="${p.titulo}">${p.titulo}</h2>

                    <p class="card-text fw-bold text-primary mb-3">
                        ${p.precio} €
                    </p>

                    <a href="detail_product.php?id=${p.id}" 
                       class="btn btn-outline-primary mt-auto w-100">
                        Ver más
                    </a>
                </div>
            </div>
        `;

        container.appendChild(col);

        //Forzamos reflow para que la animación SIEMPRE se dispare
        void col.offsetWidth;
        col.classList.add("show");
    });
}

function loadProducts() {
    fetch(`/MercApp/api/products_following.php?page=${page}&limit=8`)
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                // Si quieres, aquí puedes mostrar un mensaje en pantalla
                return;
            }

            renderProducts(data.productos);

            if (data.productos.length < 8) {
                document.getElementById("loadMore").style.display = "none";
            }
        })
        .catch(() => {
            // Error silencioso o puedes mostrar un mensaje si lo deseas
        });
}



document.getElementById("loadMore").addEventListener("click", () => {
    page++;
    loadProducts();
});

loadProducts();
</script>

</body>
</html>
