<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("location:login.php");
}

$productos = [
  ["nombre" => "Teléfono móvil", "precio" => "250€", "imagen" => "📱"],
  ["nombre" => "Portátil", "precio" => "750€", "imagen" => "💻"],
  ["nombre" => "Auriculares", "precio" => "50€", "imagen" => "🎧"],
  ["nombre" => "Cámara", "precio" => "300€", "imagen" => "📷"],
  ["nombre" => "Reloj inteligente", "precio" => "120€", "imagen" => "⌚"],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MercApp - Home</title>
  <link rel="stylesheet" href="../css/reset.css">
  <script src="../js/theme.js" defer></script>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style-guide.css">
</head>

<body>
  <!-- Navbar -->
  <?php
  $showSearch = true;
  include("navbar.php"); ?>

  <!-- Listado de productos -->
  <main class="container">
    <h2 class="mb-4 text-primary">Productos disponibles</h2>
    <div class="row g-4 sinFondo">
      <?php foreach ($productos as $producto): ?>
        <div class="col-6 col-md-4 col-lg-3 sinFondo">
          <div class="card h-100 text-center shadow-sm ">
            <div class="card-body">
              <div class="display-1"><?= $producto["imagen"] ?></div>
              <h5 class="card-title mt-3"><?= htmlspecialchars($producto["nombre"]) ?></h5>
              <p class="card-text text-muted"><?= htmlspecialchars($producto["precio"]) ?></p>
              <a href="#" class="btn button-primary w-100">Ver más</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>

</html>