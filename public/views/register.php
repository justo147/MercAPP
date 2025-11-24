<?php
session_start();
//comprobamos que la session no esta iniciada
if(isset($_SESSION["user_id"])){
  header("location:home.php");
}


if (isset($_POST["register"]) && !empty($_POST["name"]) && !empty($_POST["password"]) && !empty($_POST["confirmPass"]) && !empty($_POST["email"]) && $_POST["password"] === $_POST["confirmPass"]) {
  $name = $_POST["name"];
  $password = $_POST["password"];
  $email = $_POST["email"];


  $password_encripted = password_hash($password, PASSWORD_DEFAULT);

  try {
    // conectar a la bbdd
    $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");

    $consulta = $bd->prepare("INSERT INTO usuario(email,contraseña_hash,nombre) VALUES (?,?,?)");
    $consulta->execute([$email,  $password_encripted,$name]);

    if ($consulta->rowCount() === 1) {
  // Generar token seguro
  $verifyToken = bin2hex(random_bytes(32));

  // Guardar token en la BD
  $upd = $bd->prepare("UPDATE usuario SET verify_token = ? WHERE email = ?");
  $upd->execute([$verifyToken, $email]);

  // Crear enlace de verificación
  $verifyLink = "http://localhost/MercApp/public/views/verify_email.php?token={$verifyToken}&email=" . urlencode($email);

  // Enviar correo
  require __DIR__ . '/../../config/mail_config.php';
  $subject = "Confirma tu correo en MercaAPP";
  $body = "
    <h2>¡Bienvenido, {$name}!</h2>
    <p>Confirma tu correo para activar tu cuenta:</p>
    <p><a href='{$verifyLink}' style='background:#0d6efd;color:#fff;padding:10px 12px;border-radius:6px;text-decoration:none;'>Confirmar correo</a></p>
    <p>Si el botón no funciona, copia este enlace:<br>{$verifyLink}</p>
  ";

  sendMail($email, $name, $subject, $body);

  header("Location: pending_verification.php");
  exit;
}

  } catch (PDOException $e) {
    die($e->getMessage());
  }
}
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
        <input type="text" class="form-control border border-primary rounded" id="name" name="name" required>
      </div>

      <div class="mb-3 sinFondo">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control border border-primary rounded" id="email" name="email" required>
      </div>

      <div class="mb-3 sinFondo">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control border border-primary rounded" id="password" name="password" required>
      </div>

      <div class="mb-3 sinFondo">
        <label for="confirmPass" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control border border-primary rounded" id="confirmPass" name="confirmPass" required>
      </div>

      <button type="submit" name="register" class="btn button-primary w-100">Registrarse</button>
    </form>
  </div>

  <!-- Enlace inferior -->
  <div class="text-center mt-3 sinFondo">
    <a href="login.php" >¿Ya estás registrado? Inicia sesión aquí</a>
  </div>
</body>


</html>