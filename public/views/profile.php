<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <!-- enlaces a bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/homeStyle.css">
  <script src="../js/theme.js" defer></script>
</head>

<body>
  <?php
  $showSearch = false;
  include("navbar.php"); ?>

  <br>
<div class="perfil-box mx-auto my-4 sinFondo">
  <h2 class="mb-3 text-center">Datos del Usuario</h2>
  <ul class="list-group">
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <strong>Nombre:</strong>
      <span id="userName"></span>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <strong>Email:</strong>
      <span id="userEmail"></span>
    </li>
  </ul>
</div>





  <section class="user-products container my-5 sinFondo">
    <h2 class="mb-4">Tus productos</h2>
    <div class="row g-4 sinFondo">
      <div class="col-6 col-md-4 col-lg-3 sinFondo">
        <div class="card h-100 text-center">
          <div class="card-body">
            <p class="card-text">Producto 1</p>
            <div class="display-1">📦</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-3 sinFondo">
        <div class="card h-100 text-center">
          <div class="card-body">
            <p class="card-text">Producto 2</p>
            <div class="display-1">📦</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-3 sinFondo">
        <div class="card h-100 text-center">
          <div class="card-body">
            <p class="card-text">Producto 3</p>
            <div class="display-1">📦</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-3 sinFondo">
        <div class="card h-100 text-center">
          <div class="card-body">
            <p class="card-text">Producto 4</p>
            <div class="display-1">📦</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-3 sinFondo">
        <div class="card h-100 text-center">
          <div class="card-body">
            <p class="card-text">Producto 5</p>
            <div class="display-1">📦</div>
          </div>
        </div>
      </div>
    </div>
  </section>




</body>

</html>