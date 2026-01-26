<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: ../auth/login.php");
  exit;
}
// Ya no necesitamos cargar productos aquí 
// require_once __DIR__ . '/../../controllers/handlers/home_handlers.php';
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

  <!-- CSS personalizados -->
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/homeStyle.css">

  <!-- JS para tema oscuro/claro -->
  <script src="../js/theme.js" defer></script>

  <!-- Bootstrap CSS y JS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <link href="../css/style-guide.css" rel="stylesheet">

  <!-- JS del scroll infinito -->
  <script src="../js/home.js" defer></script>

</head>

<body>
  <!-- Navbar -->
  <?php
  $showSearch = true;
  include("navbar.php");
  ?>

  <main class="container">
    <h2 class="mb-4 text-primary">Productos disponibles</h2>

    <!-- Contenedor donde JS insertará los productos -->
    <div id="product-list" class="row g-4 sinFondo">
      <!-- Skeleton Loader -->
      <div id="skeleton-loader" class="row g-4"></div>
    </div>

    <!-- Sentinel para IntersectionObserver -->
    <div id="sentinel" style="height: 1px;"></div>



    <!-- Error -->
    <div id="error" style="display:none; color:red; text-align:center;">
      Error cargando productos
    </div>
  </main>

  <footer>
    <?php include __DIR__ . '/footer.php'; ?>
  </footer>

</body>

</html>