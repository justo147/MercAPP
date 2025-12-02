<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold " href="home.php">
      <img src="../img/logo_sinfondo.png" alt="MercApp" height="30" class="d-inline-block align-middle">
      MercApp
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <?php if (!empty($showSearch) && $showSearch === true): ?>
        <div class="ms-auto" style="max-width: 500px; width: 100%;">
          <form class="d-flex" role="search" method="get" action="home.php">
            <input class="form-control me-2" type="search" name="q" placeholder="Buscar productos..." aria-label="Buscar">
            <button class="btn btn-light" type="submit"><i class="bi bi-search"></i></button>
          </form>
        </div>
      <?php endif; ?>

      <div class="d-flex ms-auto align-items-center">
        <div class="dropdown me-2">
          <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center"
            type="button"
            id="userMenu"
            data-bs-toggle="dropdown"
            aria-expanded="false">

            <?php if (!empty($_SESSION["profile_photo"])): ?>
              <img src="<?php echo htmlspecialchars($_SESSION["profile_photo"]) ?>"
                alt="Foto de perfil"
                class="rounded-circle me-2"
                width="24" height="24">
            <?php else: ?>
              <i class="bi bi-person me-2 fs-5"></i>
            <?php endif; ?>

            Perfil
          </button>

          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
            <li>
              <a class="dropdown-item" href="profile.php?id=<?php echo htmlspecialchars($_SESSION['user_id']) ?>">
                <i class="bi bi-person"></i> Mi perfil
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#">
                <i class="bi bi-upload"></i> Subir producto
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="detailAccount.php">
                <i class="bi bi-gear"></i> Ajustes de Cuenta
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <!-- RUTA CORREGIDA para logout -->
              <a class="dropdown-item text-danger" href="../../controllers/logout.php">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
              </a>
            </li>
          </ul>
        </div>

        <button id="themeToggle" class="btn btn-outline-light rounded-circle" aria-label="Cambiar tema">🌙</button>
      </div>
    </div>
  </div>
</nav>

<script src="../js/theme.js"></script>