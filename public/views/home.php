<?php
session_start();

if(!isset($_SESSION["user_id"])){
  header("location:login.php");
}
// Aquí podrías conectar con tu base de datos y obtener productos.
// Para el ejemplo, uso un array simulado:
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
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <!-- Header con buscador -->
  <header class="bg-primary text-white p-3 mb-4">
    <div class="container d-flex justify-content-between align-items-center">
      <h1 class="h3">MercApp</h1>
      <form class="d-flex" role="search" method="get" action="buscar.php">
        <input class="form-control me-2" type="search" name="q" placeholder="Buscar productos..." aria-label="Buscar">
        <button class="btn btn-light" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </div>
  </header>

  <!-- Listado de productos -->
  <main class="container">
    <h2 class="mb-4">Productos disponibles</h2>
    <div class="row g-4">
      <?php foreach ($productos as $producto): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="card h-100 text-center shadow-sm">
            <div class="card-body">
              <div class="display-1"><?= $producto["imagen"] ?></div>
              <h5 class="card-title mt-3"><?= htmlspecialchars($producto["nombre"]) ?></h5>
              <p class="card-text text-muted"><?= htmlspecialchars($producto["precio"]) ?></p>
              <a href="#" class="btn btn-primary">Ver más</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
