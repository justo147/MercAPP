<?php

/**
 * Inicio de sesión y verificación
 * Redirige a login si no hay sesión activa
 */
session_start();
//si no esta iniciada la session
if (!isset($_SESSION["user_id"])) {
  header("location:auth/login.php");
}

$perfilId = intval($_GET["id"] ?? 0);
//si la url no tiene parametro id
if ($perfilId == 0) {
  header("location:auth/login.php");
}
$usuarioLogueado = $_SESSION["user_id"];

$esPropietario = ($perfilId === $usuarioLogueado);

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
  <style>
    #badge-mensajes {
      transition: opacity 0.2s ease;
    }
  </style>
</head>

<body>
  <main>
    <!-- Navbar con opciones de perfil -->
    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <br>

    <!-- Contenedor del perfil -->
    <div class="perfil-box mx-auto my-4 sinFondo">
      <h1 class="mb-3 text-center">Datos del Usuario</h1>

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
                    //Si usuario no existe mostrar pagina error
                    if (!$user) {
                      ?>
                      <div class="container text-center mt-5">
                        <h1 class="display-5 fw-bold text-danger">Usuario no encontrado</h1>
                        <p class="lead">El perfil que intentas ver no existe o ha sido eliminado.</p>
                        <a href="/MercApp/public/views/home.php" class="btn btn-primary mt-3">
                          Volver al inicio
                        </a>
                      </div>
                      <?php
                      exit;
                    }
                  } catch (PDOException $e) {
                    error_log("Error de base de datos: " . $e->getMessage());
                  }
                }
                ?>

                <!-- Mostrar foto de perfil o icono por defecto -->
                <?php if ($user && !empty($user['foto_perfil'])): ?>
                  <img src="/MercApp/<?= htmlspecialchars($user['foto_perfil']) ?>" class="rounded-circle mt-5"
                    width="120" height="120" style="object-fit: cover;" alt="Foto de perfil">
                <?php else: ?>
                  <i class="bi bi-person-circle mb-3 text-secondary" style="font-size: 120px; display: block;"></i>
                <?php endif; ?>
              </div>

              <!-- Información del usuario -->
              <div class="flex-grow-1 ms-3 sinFondo">
                <h2 class="mb-1"><?= htmlspecialchars($user['nombre'] . " " . $user["apellidos"]) ?></h2>
                <p class="mb-2 pb-1 fs-6 text-muted">
                  Cuenta creada:
                  <?php
                  $fecha = new DateTime($user['fecha_registro']);
                  $mes = $fecha->format('m');
                  $anio = $fecha->format('Y');
                  echo "$mes/$anio";
                  ?>
                </p>

                <!-- Estadísticas del usuario -->
                <div class="d-flex justify-content-between text-center rounded-3 p-2 mb-2"
                  style="background-color: rgb(245, 245, 245);" role="group" aria-label="Estadísticas de usuario">

                  <div class="flex-fill sinFondo">
                    <p id="label-productos" class="small text-body-secondary mb-1">Productos</p>
                    <h3 class="mb-0 fs-4 fw-bold" aria-labelledby="label-productos" id="stat-productos">15</h3>
                  </div>

                  <div class="flex-fill mx-4 sinFondo">
                    <p id="label-ventas" class="small text-body-secondary mb-1">Ventas</p>
                    <h3 class="mb-0 fs-4 fw-bold" aria-labelledby="label-ventas" id="stat-ventas">515</h3>
                  </div>

                  <div class="flex-fill sinFondo">
                    <p id="label-valoracion" class="small text-body-secondary mb-1">Valoración</p>
                    <h3 class="mb-0 fs-4 fw-bold" aria-labelledby="label-valoracion" id="stat-valoracion">9.2</h3>
                  </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex">
                  <a href="/MercApp/public/views/chat_list.php"
                    class="btn btn-outline-primary me-1 flex-grow-1 position-relative">
                    Message
                    <span id="badge-mensajes"
                      class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger">
                    </span>
                  </a>

                  <?php
                  // Mostrar botón Follow si no es el propio perfil
                  if (!$esPropietario) {
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

   <div class="container my-4">
    <h2 class="mb-3">Mis productos</h2>
    <label for="buscador-productos" class="form-label">Buscar en mis productos:</label>
    <input type="text" id="buscador-productos" class="form-control mb-3" placeholder="Ej: Camiseta de algodón...">
    <div id="productos-usuario" class="row g-3"></div>
</div>


    <div class="d-flex justify-content-center mt-4" id="paginacion-productos"></div>


    <script>
      const PERFIL_ID = <?= $perfilId ?>;
      const ES_PROPIETARIO = <?= $esPropietario ? 'true' : 'false' ?>;
    </script>

    <script src="../js/productosPerfil.js"></script>

    <footer>
      <?php include __DIR__ . '/footer.php'; ?>
    </footer>
  </main>

  <!-- Modal Eliminar Producto -->
  <div class="modal fade" id="modalEliminarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header bg-danger text-white">
          <h4 class="modal-title">Eliminar producto</h4>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <p>¿Seguro que quieres eliminar este producto? Esta acción no se puede deshacer.</p>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>

          <a id="btnConfirmarEliminar" class="btn btn-danger">
            Eliminar definitivamente
          </a>
        </div>

      </div>
    </div>
  </div>

  <!-- Toast de eliminación -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    <div id="toastEliminado" class="toast align-items-center text-bg-success border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body"> Producto eliminado correctamente. </div> <button type="button"
          class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener("DOMContentLoaded", () => {

      const modal = document.getElementById("modalEliminarProducto");
      const btnConfirmar = document.getElementById("btnConfirmarEliminar");
      const params = new URLSearchParams(window.location.search);

      modal.addEventListener("show.bs.modal", event => {
        const button = event.relatedTarget;
        const productId = button.getAttribute("data-product-id");

        // Establecer la URL del botón de confirmación
        btnConfirmar.href = `/MercApp/controllers/handlers/delete_product_handler.php?id=${productId}`;
      });

      if (params.has("deleted")) {
        const toast = new bootstrap.Toast(document.getElementById("toastEliminado"));
        toast.show();
      }

    });
  </script>

</body>

</html>