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
    <!-- card del usuario -->
    <div class="col col-md-9 col-lg-7 col-xl-5 sinFondo">
      <div class="card no-hover sinFondo">
        <div class="card-body p-4 no-hover sinFondo">
          <!-- content -->
          <div class="d-flex sinFondo">
            <div class="flex-shrink-0 sinFondo me-4 mt-3">
              <!-- imagen -->
              <!-- logica de si no tiene foto se ponga una predeterminada y si tiene la obtenga del servidor -->
              <?php
              session_start();
              require_once __DIR__ . '/../../config/db.php';
              $user = null;
              if (isset($_SESSION['email'])) {
                $email = $_SESSION['email'];
                try {
                  $database = new Database();
                  $pdo = $database->getConnection();
                  $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ?");
                  $stmt->execute([$email]);
                  $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                  error_log("Error de base de datos: " . $e->getMessage());
                }
              }
              ?>
              <?php if ($user && !empty($user['foto_perfil'])): ?>
                <img src="<?= htmlspecialchars($user['foto_perfil']) ?>" class="rounded-circle mt-5" width="120"
                  height="120" style="object-fit: cover;" alt="Foto de perfil">
              <?php else: ?>
                <i class="bi bi-person-circle mb-3 text-secondary" style="font-size: 120px; display: block;"></i>
              <?php endif; ?>
            </div>
            <div class="flex-grow-1 ms-3 sinFondo">
              <!-- Contenido -->
              <h5 class="mb-1"><?php echo $user['nombre'] . " " . $user["apellidos"] ?></h5>
              <p class="mb-2 pb-1 fs-6 text-muted">Cuenta creada:<?php
                                                        $fecha = new DateTime($user['fecha_registro']);
                                                        $mes   = $fecha->format('m'); // mes en número (01-12)
                                                        $anio  = $fecha->format('Y'); // año en 4 dígitos

                                                        echo "$mes/$anio";
                                                        ?></p>
              <div class="d-flex justify-content-between text-center rounded-3 p-2 mb-2"
                style="background-color: rgb(186, 185, 185);">
                <div class="flex-fill sinFondo">
                  <p class="small text-body-secondary mb-1">Productos</p>
                  <p class="mb-0 fs-4 fw-bold">15</p>
                </div>
                <div class="flex-fill mx-4 sinFondo">
                  <p class="small text-body-secondary mb-1">Ventas</p>
                  <p class="mb-0 fs-4 fw-bold">515</p>
                </div>
                <div class="flex-fill sinFondo">
                  <p class="small text-body-secondary mb-1">Valoración</p>
                  <p class="mb-0 fs-4 fw-bold">9.2</p>
                </div>
              </div>


              <!-- Botones -->
              <div class="d-flex">
                <button type="button" class="btn btn-outline-primary me-1 flex-grow-1 position-relative">
                  Message
                  <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger">
                    10
                  </span>
                </button>

                <button class="flex-grow-1 btn btn-primary">
                  Follow
                </button>

              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
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