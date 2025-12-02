<?php
// RUTA CORREGIDA para register_handlers.php
require_once __DIR__ . '/../../../controllers/handlers/register_handlers.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro de MercApp</title>

  <!-- Favicon (AÑADIDO) -->
  <link rel="icon" href="../../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../../ico/logo_sinfondo.ico" type="image/x-icon">

  <!-- Bootstrap y estilos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Estilos propios (RUTAS CORREGIDAS) -->
  <link rel="stylesheet" href="../../css/reset.css">
  <link rel="stylesheet" href="../../css/style-guide.css">
  <link rel="stylesheet" href="../../css/loginStyle.css"> <!-- AÑADIR si usas este CSS -->

  <!-- Scripts de validación y tema (RUTAS CORREGIDAS) -->
  <script src="../../js/registerValidation.js" defer></script>
  <script src="../../js/theme.js" defer></script>
</head>

<body class="d-flex flex-column align-items-center justify-content-center min-vh-100">
  <!-- Botón de cambio de tema -->
  <button id="themeToggle" class="toggle-btn position-fixed top-0 end-0 m-3" aria-label="Cambiar tema">🌙</button>

  <!-- Logo (RUTA CORREGIDA) -->
  <div class="imageLogo sinFondo mb-4">
    <img src="../../img/logo_sinfondo.png" alt="Logo de MercaAPP" class="img-fluid" style="max-width: 200px;">
  </div>

  <!-- Contenedor del formulario -->
  <div class="container shadow p-4 sinFondo" style="max-width: 400px; width: 100%;">
    <form id="formRegistro" method="post" class="form">
      <h1 class="text-center mb-4">Registrar Cuenta</h1>

      <!-- Campo Nombre -->
      <div class="mb-3 sinFondo">
        <label for="name" class="form-label">Nombre</label>
        <input type="text" class="form-control border border-primary rounded" id="name" name="name" required>
      </div>

      <!-- Campo Email -->
      <div class="mb-3 sinFondo">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control border border-primary rounded" id="email" name="email" required>
      </div>

      <!-- Campo Contraseña -->
      <div class="mb-3 sinFondo">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control border border-primary rounded" id="password" name="password" required>
      </div>

      <!-- Campo Confirmar Contraseña -->
      <div class="mb-3 sinFondo">
        <label for="confirmPass" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control border border-primary rounded" id="confirmPass" name="confirmPass" required>
      </div>

      <!-- Botón de envío -->
      <button type="submit" name="register" class="btn button-primary w-100">Registrarse</button>
    </form>

    <!-- Contenedor para mostrar mensajes del servidor -->
    <div id="respuesta" class="mt-3"></div>
  </div>

  <!-- Enlace inferior (RUTA CORREGIDA) -->
  <div class="text-center mt-3 sinFondo">
    <a href="login.php">¿Ya estás registrado? Inicia sesión aquí</a>
  </div>
</body>
</html>