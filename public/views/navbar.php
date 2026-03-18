<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm" aria-label="Navegación principal">
  <div class="container-fluid">

    <a class="navbar-brand fw-bold" href="home.php">
      <img src="../img/logo_sinfondo.png" height="30" class="d-inline-block align-middle">
      MercApp
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">

      <div class="w-100 d-flex flex-column flex-lg-row align-items-lg-center">

        <div class="d-flex align-items-center justify-content-end order-1 order-lg-2 ms-lg-auto mb-3 mb-lg-0">

          <!-- Nuevo botón de seguidores -->
          <a href="followers_products.php?id=<?php echo $_SESSION['user_id'] ?>"
            class="btn btn-outline-light me-2 d-flex align-items-center">
            <i class="bi bi-people-fill me-1"></i> Seguidores
          </a>

          <div class="dropdown me-2">
            <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" id="userMenu"
              data-bs-toggle="dropdown">

              <?php if (!empty($_SESSION["profile_photo"])): ?>
                <img src="/MercApp/<?php echo htmlspecialchars($_SESSION["profile_photo"]) ?>" class="rounded-circle me-2"
                  width="24" height="24">
              <?php else: ?>
                <i class="bi bi-person me-2 fs-5"></i>
              <?php endif; ?>

              Perfil
            </button>

            <ul class="dropdown-menu dropdown-menu-end">

              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li>
                  <a class="dropdown-item fw-bold text-primary bg-light" href="admin_dashboard.php">
                    <i class="bi bi-shield-lock"></i> Panel de Admin
                  </a>
                </li>
                <li>
                  <hr class="dropdown-divider">
                </li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="profile.php?id=<?php echo $_SESSION['user_id'] ?>"><i
                    class="bi bi-person"></i> Mi perfil</a></li>
              <li><a class="dropdown-item d-flex align-items-center"
                  href="chat_list.php?id=<?php echo $_SESSION['user_id'] ?>"><i class="bi bi-envelope me-2"></i>Mensajes
                  <span id="badge-mensajes" class="badge bg-danger ms-2"></span></a></li>
              <li><a class="dropdown-item" href="upload_product.php"><i class="bi bi-upload"></i> Subir producto</a>
              </li>
              <li><a class="dropdown-item" href="../../public/views/help.php"><i class="bi bi-question-circle"></i>
                  Ayuda</a></li>
              <li><a class="dropdown-item" href="detail_account.php"><i class="bi bi-gear"></i> Ajustes de Cuenta</a>
              </li>
              <li><a class="dropdown-item" href="../../public/views/docs.php"><i class="bi bi-book"></i>
                  Documentación</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item text-danger" href="../../controllers/logout.php"><i
                    class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
            </ul>
          </div>

          <button id="themeToggle" class="btn btn-outline-light rounded-circle">🌙</button>
        </div>

        <?php if (!empty($showSearch) && $showSearch === true): ?>
          <div class="order-2 order-lg-1 mx-lg-auto" style="max-width: 500px; width: 100%;">
            <form class="d-flex" method="get" action="home.php">
              <input class="form-control me-2" type="search" name="q" placeholder="Buscar productos..."
                value="<?php echo htmlspecialchars($_GET['q'] ?? '') ?>">
              <button class="btn btn-light" type="submit"><i class="bi bi-search"></i></button>
            </form>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</nav>

<script src="../js/theme.js"></script>
<script src="/MercApp/public/js/navbar.js"></script>