<?php
// ===============================
// INICIO DE SESIÓN Y CONTROL DE ACCESO
// ===============================
session_start(); // Iniciamos la sesión para poder comprobar si el usuario ya está logueado

// Si ya existe una sesión activa (usuario logueado), lo redirigimos al home
if (isset($_SESSION["user_id"])) {
  header("location:home.php");
  exit;
}

// ===============================
// PROCESAMIENTO DE PETICIONES POST (cuando se envía el formulario)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // --- VALIDACIONES BÁSICAS EN SERVIDOR ---
  if (empty($_POST["name"]) || empty($_POST["password"]) || empty($_POST["confirmPass"]) || empty($_POST["email"])) {
    echo "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
    exit;
  } elseif (strlen($_POST["password"]) < 8) {
    echo "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
    exit;
  } elseif ($_POST["password"] !== $_POST["confirmPass"]) {
    echo "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
    exit;
  } elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
    echo "<div class='alert alert-danger'>El correo tiene que ser válido.</div>";
    exit;
  } else {
    // --- SI LAS VALIDACIONES PASAN ---
    $name = $_POST["name"];
    $password = $_POST["password"];
    $email = $_POST["email"];

    // Encriptamos la contraseña con un hash seguro
    $password_encripted = password_hash($password, PASSWORD_DEFAULT);

    try {
      // ===============================
      // CONEXIÓN A LA BASE DE DATOS
      // ===============================
      $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");

      // Comprobamos si el email ya existe en la tabla usuario
      $consulta = $bd->prepare("SELECT * FROM usuario WHERE email=?");
      $consulta->execute([$email]);

      if ($consulta->rowCount() == 1) {
        echo "<div class='alert alert-danger'>El correo electrónico ya está registrado.</div>";
        exit;
      }

      // ===============================
      // INSERCIÓN DEL NUEVO USUARIO
      // ===============================
      $consulta = $bd->prepare("INSERT INTO usuario(email,contraseña_hash,nombre) VALUES (?,?,?)");
      $consulta->execute([$email, $password_encripted, $name]);

      if ($consulta->rowCount() === 1) {
        // Generamos un token de verificación único
        $verifyToken = bin2hex(random_bytes(32));

        // Guardamos el token en la BD asociado al usuario
        $upd = $bd->prepare("UPDATE usuario SET verify_token = ? WHERE email = ?");
        $upd->execute([$verifyToken, $email]);

        // ===============================
        // ENVÍO DE CORREO DE VERIFICACIÓN
        // ===============================
        require __DIR__ . '/../../config/mail_config.php';
        $subject = "Confirma tu correo en MercaAPP";
        $body = "Bienvenido {$name}, confirma tu correo: http://localhost/MercApp/public/views/verify_email.php?token={$verifyToken}&email=" . urlencode($email);

        // Función sendMail definida en mail_config.php
        sendMail($email, $name, $subject, $body);

        // ===============================
        // RESPUESTA DE ÉXITO AL CLIENTE
        // ===============================
        echo "<div class='alert alert-success'>
                Registro correcto. Revisa tu correo para confirmar.
              </div>";

        // Redirección automática tras 2 segundos
        echo "<script>
                setTimeout(function() {
                  window.location.href = 'pending_verification.php';
                }, 2000);
              </script>";
        exit;
      }

    } catch (Exception $e) {
      // Si ocurre un error en la BD, mostramos mensaje de error
      echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
      exit;
    }
  }
}

// ===============================
// SI LA PETICIÓN ES GET (el usuario entra desde el navegador)
// SE MUESTRA EL FORMULARIO DE REGISTRO
// ===============================
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro de MercApp</title>

  <!-- Bootstrap y estilos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Estilos propios -->
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">

  <!-- Scripts de validación y tema -->
  <script src="../js/registerValidation.js" defer></script>
  <script src="../js/theme.js" defer></script>
</head>

<body class="d-flex flex-column align-items-center justify-content-center min-vh-100">
  <!-- Botón de cambio de tema -->
  <button id="themeToggle" class="toggle-btn position-fixed top-0 end-0 m-3" aria-label="Cambiar tema">🌙</button>

  <!-- Logo -->
  <div class="imageLogo sinFondo mb-4">
    <img src="../img/logo_sinfondo.png" alt="Logo de MercaAPP" class="img-fluid" style="max-width: 200px;">
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

  <!-- Enlace inferior -->
  <div class="text-center mt-3 sinFondo">
    <a href="login.php">¿Ya estás registrado? Inicia sesión aquí</a>
  </div>
</body>
</html>
