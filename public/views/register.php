<?php
session_start(); // Iniciamos la sesión para poder comprobar si el usuario ya está logueado

// Si la petición es POST (es decir, el formulario se envía con fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validaciones básicas en servidor
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
    // Si pasa las validaciones básicas, recogemos los datos
    $name = $_POST["name"];
    $password = $_POST["password"];
    $email = $_POST["email"];
    $password_encripted = password_hash($password, PASSWORD_DEFAULT); // Encriptamos la contraseña

    try {
      // Conexión a la base de datos
      $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");

      // Comprobamos si el email ya existe
      $consulta = $bd->prepare("SELECT * FROM usuario WHERE email=?");
      $consulta->execute([$email]);

      if ($consulta->rowCount() == 1) {
        echo "<div class='alert alert-danger'>El correo electrónico ya está registrado.</div>";
        exit;
      }

      // Insertamos el nuevo usuario
      $consulta = $bd->prepare("INSERT INTO usuario(email,contraseña_hash,nombre) VALUES (?,?,?)");
      $consulta->execute([$email, $password_encripted, $name]);

      if ($consulta->rowCount() === 1) {
        // Generamos un token de verificación
        $verifyToken = bin2hex(random_bytes(32));
        $upd = $bd->prepare("UPDATE usuario SET verify_token = ? WHERE email = ?");
        $upd->execute([$verifyToken, $email]);

        // Enviamos correo de verificación
        require __DIR__ . '/../../config/mail_config.php';
        $subject = "Confirma tu correo en MercaAPP";
        $body = "Bienvenido {$name}, confirma tu correo: http://localhost/MercApp/public/views/verify_email.php?token={$verifyToken}&email=" . urlencode($email);
        sendMail($email, $name, $subject, $body);

        // Si todo va bien, devolvemos directamente HTML
        echo "<div class='alert alert-success'>
            Registro correcto. Revisa tu correo para confirmar.
          </div>";

        // Opcional: redirigir automáticamente tras unos segundos
        echo "<script>
            setTimeout(function() {
              window.location.href = 'pending_verification.php';
            }, 2000);
          </script>";
        exit;
      }

    } catch (Exception $e) {
      echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
      exit;

    }
  }
}

// Si la petición es GET (el usuario entra a la página desde el navegador),
// mostramos el formulario normalmente en HTML.
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro de MercApp</title>
  <!-- enlaces a bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
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

      <div class="mb-3 sinFondo">
        <label for="name" class="form-label">Nombre</label>
        <input type="text" class="form-control border border-primary rounded" id="name" name="name" value="<?php if (isset($_POST["register"])) {
          echo htmlspecialchars($_POST['name']);
        } ?>" required>
      </div>

      <div class="mb-3 sinFondo">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control border border-primary rounded <?php if (isset($emailError))
          echo 'is-invalid'; ?>" id="email" name="email" value="<?php if (isset($_POST["register"])) {
              echo htmlspecialchars($_POST['email']);
            } ?>" required>
        <?php if (isset($emailError)): ?>
          <div class="invalid-feedback">
            <?php echo $error; ?>
          </div>
        <?php endif; ?>
      </div>


      <div class="mb-3 sinFondo">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control border border-primary rounded <?php if (isset($passwordError))
          echo 'is-invalid'; ?>" id="password" name="password" required>
        <?php if (isset($passwordError)): ?>
          <div class="invalid-feedback">
            <?php echo $error; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="mb-3 sinFondo">
        <label for="confirmPass" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control border border-primary rounded <?php if (isset($passError))
          echo 'is-invalid'; ?>" id="confirmPass" name="confirmPass" required>
        <?php if (isset($passError)): ?>
          <div class="invalid-feedback">
            <?php echo $error; ?>
          </div>
        <?php endif; ?>
      </div>


      <button type="submit" name="register" class="btn button-primary w-100">Registrarse</button>
    </form>
    <!-- contenedor para mostrar mensajes -->
    <div id="respuesta" class="mt-3"></div>
  </div>

  <!-- Enlace inferior -->
  <div class="text-center mt-3 sinFondo">
    <a href="login.php">¿Ya estás registrado? Inicia sesión aquí</a>
  </div>
</body>


</html>