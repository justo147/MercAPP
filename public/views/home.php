<?php
// Incluye el handler que prepara los datos para la página de inicio
// (en este caso, el array $productos)
require_once __DIR__ . '/../../controllers/handlers/home_handlers.php';
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
  <link rel="stylesheet" href="../css/style-guide.css">
  
  <!-- JS para tema oscuro/claro -->
  <script src="../js/theme.js" defer></script>
  
  <!-- Bootstrap CSS y JS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <link href="../css/style-guide.css" rel="stylesheet">

</head>

<body>
  <!-- Navbar -->
  <?php
  $showSearch = true; // Mostrar barra de búsqueda en el navbar
  include("navbar.php");
  ?>

  <main class="container">
    <h2 class="mb-4 text-primary">Productos disponibles</h2>

    <div class="row g-4 sinFondo">
      <!-- Iteración sobre el array de productos para mostrar cada producto en una card -->
      <?php foreach ($productos as $producto): ?>
        <div class="col-6 col-md-4 col-lg-3 sinFondo">
          <div class="card h-100 text-center shadow-sm">
            <div class="card-body">
              <!-- Emoji o imagen representando el producto -->
              <div class="display-1"><?= $producto["imagen"] ?></div>
              
              <!-- Nombre del producto -->
              <h5 class="card-title mt-3"><?= htmlspecialchars($producto["nombre"]) ?></h5>
              
              <!-- Precio del producto -->
              <p class="card-text text-muted"><?= htmlspecialchars($producto["precio"]) ?></p>
              
              <!-- Botón de acción -->
              <a href="#" class="btn button-primary w-100">Ver más</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
