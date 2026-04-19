<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';
if (!isset($_SESSION["user_id"])) {
  header("Location: {$BASE}/public/views/auth/login.php");
exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MercApp - Home</title>

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
  <script src="../js/home.js" defer></script>

  <link href="../css/style-guide.css" rel="stylesheet">
</head>

<body>

  <!-- Navbar -->
  <?php
  $showSearch = true;
  include("navbar.php");
  ?>

  <main class="container">

    <!-- Filtros -->
    <div class="card shadow-sm border mb-4 no-hover">
      <div class="card-body">
        <div class="row g-3">

          <!-- Categoría -->
          <div class="col-6 col-md-3">
            <label for="filtro-categoria" class="visually-hidden">Filtrar por Categoría</label>
            <select id="filtro-categoria" class="form-select">
              <option value="">Categoría</option>
            </select>
          </div>

          <!-- Estado del producto -->
          <div class="col-6 col-md-3">
            <label for="filtro-estado" class="visually-hidden">Filtrar por Estado del producto</label>
            <select id="filtro-estado" class="form-select">
              <option value="">Estado</option>
            </select>
          </div>

          <!-- Tipo de transacción -->
          <div class="col-6 col-md-3">
            <label for="filtro-transaccion" class="visually-hidden">Filtrar por Tipo de transacción</label>
            <select id="filtro-transaccion" class="form-select">
              <option value="">Transacción</option>
            </select>
          </div>

          <!-- Orden -->
          <div class="col-6 col-md-3">
            <label for="filtro-orden" class="visually-hidden">Ordenar resultados</label>
            <select id="filtro-orden" class="form-select">
              <option value="fecha_desc">Más recientes</option>
              <option value="fecha_asc">Más antiguos</option>
              <option value="precio_asc">Precio: menor a mayor</option>
              <option value="precio_desc">Precio: mayor a menor</option>
              <option value="distancia">Más cercanos</option>
            </select>
          </div>

          <!-- Proximidad -->
          <div class="col-12" id="filtro-proximidad-wrap" style="display:none;">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <i class="bi bi-geo-alt-fill text-primary"></i>
              <span class="small" id="proximidad-coords-label">Detectando ubicación…</span>
              <select id="filtro-distancia" class="form-select form-select-sm" style="width:auto;">
                <option value="5">5 km</option>
                <option value="10" selected>10 km</option>
                <option value="25">25 km</option>
                <option value="50">50 km</option>
                <option value="100">100 km</option>
              </select>
              <button class="btn btn-sm btn-outline-secondary" id="btn-geolocalizar">
                <i class="bi bi-crosshair"></i> Usar mi ubicación
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <h1 class="mb-4 text-primary">Productos disponibles</h1>

    <!-- Skeleton Loader (DEBE IR AQUÍ) -->
    <div id="skeleton-loader" class="row g-4"></div>

    <!-- Contenedor donde JS insertará los productos -->
    <div id="product-list" class="row g-4 sinFondo"></div>

    <!-- Sentinel para scroll infinito -->
    <div id="sentinel" style="height: 1px;"></div>

    <!-- Error -->
    <div id="error" style="display:none; color:red; text-align:center;">
      Error cargando productos
    </div>

  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

</body>

</html>