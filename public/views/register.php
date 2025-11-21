<?php
session_start();

if (isset($_POST["register"]) && !empty($_POST["name"]) && !empty($_POST["password"]) && !empty($_POST["confirmPass"]) && !empty($_POST["email"]) && $_POST["password"] === $_POST["confirmPass"]) {
  $name = $_POST["name"];
  $password = $_POST["password"];
  $email = $_POST["email"];


  $password_encripted = password_hash($password, PASSWORD_DEFAULT);

  try {
    // conectar a la bbdd
    $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");

    $consulta = $bd->prepare("INSERT INTO usuario(email, nick, contraseña_hash) VALUES (?,?,?)");
    $consulta->execute([$email, $name, $password_encripted]);

    if ($consulta->rowCount() == 1) {
      header("Location: login.php");
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
  <title>Document</title>
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
  <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
    <form id="formRegistro" method="post">
      <h1 class="text-center mb-4">Registrar Cuenta</h1>

      <div class="mb-3 ">
        <label for="name" class="form-label">Nick</label>
        <input type="text" class="form-control border border-primary rounded" id="name" name="name" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control border border-primary rounded" id="email" name="email" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control border border-primary rounded" id="password" name="password" required>
      </div>

      <div class="mb-3">
        <label for="confirmPass" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control border border-primary rounded" id="confirmPass" name="confirmPass" required>
      </div>

      <button type="submit" name="register" class="btn button-primary w-100">Registrarse</button>
    </form>
  </div>

  <!-- Enlace inferior -->
  <div class="text-center mt-3">
    <a href="login.php">¿Ya estás registrado? Inicia sesión aquí</a>
  </div>
</body>


</html>