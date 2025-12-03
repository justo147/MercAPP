<?php
/**
 * Inicio de sesión y verificación
 * Redirige a login si no hay sesión activa
 */
session_start();
if(!isset($_SESSION["user_id"])){
  header("location:auth/login.php");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil de <?php echo $_SESSION["name"] ?? 'Usuario' ?></title>
  
  <!-- Favicon -->
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  
  <!-- Bootstrap CSS y JS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS personalizado -->
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link rel="stylesheet" href="../css/homeStyle.css">
  
  <!-- JS de tema -->
  <script src="../js/theme.js" defer></script>
</head>

<body>
  <!-- Navbar con opciones de perfil -->
  <?php
  $showSearch = false;
  include("navbar.php");
  ?>

  <br>

  <!-- Contenedor del perfil -->
  <div class="perfil-box mx-auto my-4 sinFondo">
    <h2 class="mb-3 text-center">Datos del Usuario</h2>

    <!-- Card con info del usuario -->
    <div class="col col-md-9 col-lg-7 col-xl-5 sinFondo">
      <div class="card no-hover sinFondo">
        <div class="card-body p-4 no-hover sinFondo">
          <div class="d-flex sinFondo">

            <!-- Imagen de perfil -->
            <div class="flex-shrink-0 sinFondo me-4 mt-3">
              <?php
              require_once __DIR__ . '/../../config/db.php';
              $user = null;

              // Obtener info del usuario según ID en GET
              if (isset($_GET['id'])) {
                $id = $_GET['id'];
                try {
                  $database = new Database();
                  $pdo = $database->getConnection();
                  $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id = ?");
                  $stmt->execute([$id]);
                  $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                  error_log("Error de base de datos: " . $e->getMessage());
                }
              }
              ?>
              
              <!-- Mostrar foto de perfil o icono por defecto -->
              <?php if ($user && !empty($user['foto_perfil'])): ?>
                <img src="<?= htmlspecialchars($user['foto_perfil']) ?>" class="rounded-circle mt-5" width="120"
                  height="120" style="object-fit: cover;" alt="Foto de perfil">
              <?php else: ?>
                <i class="bi bi-person-circle mb-3 text-secondary" style="font-size: 120px; display: block;"></i>
              <?php endif; ?>
            </div>

            <!-- Información del usuario -->
            <div class="flex-grow-1 ms-3 sinFondo">
              <h5 class="mb-1"><?= htmlspecialchars($user['nombre'] . " " . $user["apellidos"]) ?></h5>
              <p class="mb-2 pb-1 fs-6 text-muted">
                Cuenta creada: 
                <?php
                  $fecha = new DateTime($user['fecha_registro']);
                  $mes   = $fecha->format('m'); 
                  $anio  = $fecha->format('Y'); 
                  echo "$mes/$anio";
                ?>
              </p>

              <!-- Estadísticas del usuario -->
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

              <!-- Botones de acción -->
              <div class="d-flex">
                <button type="button" class="btn btn-outline-primary me-1 flex-grow-1 position-relative">
                  Message
                  <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger">
                    10
                  </span>
                </button>

                <?php
                // Mostrar botón Follow si no es el propio perfil
                if($_GET['id'] != $_SESSION['user_id']){
                  echo "<button class='flex-grow-1 btn btn-primary'>Follow</button>";
                } 
                ?>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Sección de productos del usuario -->
  <section class="user-products container my-5 sinFondo">
    <h2 class="mb-4">Tus productos</h2>
    <div class="row g-4 sinFondo">
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="col-6 col-md-4 col-lg-3 sinFondo">
          <div class="card h-100 text-center">
            <div class="card-body">
              <p class="card-text">Producto <?= $i ?></p>
              <div class="display-1">📦</div>
            </div>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  </section>

</body>
</html>
