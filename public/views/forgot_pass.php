<?php
// Incluye el handler que procesa la lógica del formulario de "Olvidar Contraseña"
// (envío de correo con enlace de restablecimiento)
require __DIR__ . "/../../controllers/handlers/process_forgot_handlers.php";
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Olvidar Contraseña</title>

  <!-- Bootstrap CSS y JS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS personalizados -->
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">

  <!-- JS para tema oscuro/claro -->
  <script src="../js/theme.js" defer></script>
</head>

<body class="d-flex flex-column align-items-center justify-content-center min-vh-100">

  <!-- Botón de cambio de tema -->
  <button id="themeToggle" class="toggle-btn position-fixed top-0 end-0 m-3" aria-label="Cambiar tema">
    <i class="bi bi-moon"></i>
  </button>

  <!-- Logo de la aplicación -->
  <div class="imageLogo sinFondo mb-4">
    <img src="../img/logo_sinfondo.png" alt="Logo de MercaAPP" class="img-fluid" style="max-width: 200px;">
  </div>

  <!-- Contenedor del formulario de recuperación -->
  <div class="container shadow p-4 rounded-3 sinFondo" style="max-width: 400px; width: 100%;">
    
    <form id="formReset" method="post" class="form">
      <h1 class="text-center mb-4">Recuperar Contraseña</h1>

      <!-- Campo de correo electrónico -->
      <div class="mb-3 sinFondo">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control border border-primary rounded" id="email" name="email" required>
      </div>

      <!-- Botón para enviar enlace de recuperación -->
      <button type="submit" name="reset" class="btn button-primary">Enviar enlace</button>
    </form>

    <!-- Mensaje informativo tras envío del enlace -->
    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-info mt-3 text-center">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Enlace para volver al login -->
  <div class="text-center mt-3 sinFondo">
    <a href="login.php">Volver al inicio de sesión</a>
  </div>
</body>

</html>