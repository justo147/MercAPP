<?php
// RUTA CORREGIDA para login_handlers.php
// Desde: public/views/auth/login.php
// Hacia: handlers/login_handlers.php (subir 3 niveles hasta la raíz)
require_once __DIR__ . '../../../../controllers/handlers/login_handlers.php';

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión - MercApp</title>

  <!-- Favicon -->
  <link rel="icon" href="../../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../../ico/logo_sinfondo.ico" type="image/x-icon">

  <!-- Bootstrap y estilos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Estilos propios -->
  <link rel="stylesheet" href="../../css/loginStyle.css">
  <link rel="stylesheet" href="../../css/reset.css">
  <link rel="stylesheet" href="../../css/style-guide.css">

  <!-- Scripts -->
  <script src="../../js/login.js" defer></script>
  <script src="../../js/theme.js" defer></script>

  <style>
    .error-message {
      color: #ff4444;
      background-color: #ffe6e6;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
      text-align: center;
      font-size: 0.9em;
    }
  </style>
</head>

<body>

  <header>
    <button id="themeToggle" class="toggle-btn" aria-label="Cambiar tema">🌙</button>
    <div class="imageLogo sinFondo">
      <img src="../../img/logo_sinfondo.png" alt="Logo de MercaAPP" class="imageLogo">
    </div>
  </header>

  <div class="container d-flex justify-content-center align-items-center sinFondo" id="container">
    <form id="formLogin" method="post" class="form">
      <h1>Iniciar Sesión</h1>

      <div class="d-flex justify-content-center gap-2 my-3 sinFondo">
        <a href="#" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
          <i class="bi bi-facebook"></i>
        </a>
        <a href="#" class="btn btn-outline-warning rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
          <i class="bi bi-google"></i>
        </a>
        <a href="#" class="btn btn-outline-info rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
          <i class="bi bi-linkedin"></i>
        </a>
      </div>

      <span>Use su cuenta</span>

      <?php if (!empty($error_message)): ?>
        <div class="error-message">
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <input type="email" id="email" name="email" class="form-control border border-primary rounded" placeholder="Escriba su correo" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" />
      <input type="password" id="password" name="password" class="form-control border border-primary rounded" placeholder="Contraseña" required />

      <a href="forgotpass.php">¿Olvidaste tu contraseña?</a>
      <input type="submit" name="login" value="Iniciar Sesión" class="button-primary" />
    </form>
  </div>

  <br>
  <div class="text-center sinFondo">
    <a href="register.php">¿No tienes cuenta? Regístrate aquí</a>
  </div>
</body>
</html>
