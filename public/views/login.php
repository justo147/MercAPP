<?php
session_start();


if (isset($_POST["login"]) && !empty($_POST['email']) && !empty($_POST['password'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];

  try {
    $bd = new PDO('mysql:host=localhost;dbname=mercapp;charset=utf8', 'root', '');

    $consulta = $bd->prepare("SELECT * FROM usuario WHERE email = ?");
    $consulta->execute([$email]);
    $usuario = $consulta->fetch();

    if ($usuario && password_verify($password, $usuario['contraseña_hash'])) {
      header("Location: home.html");

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
  <title>Iniciar Sesión</title>
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">


  <script src="../js/login.js" defer></script>
  <link rel="stylesheet" href="../css/loginStyle.css">
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <script src="../js/theme.js" defer></script>
</head>


<body>
  <header>
    <button id="themeToggle" class="toggle-btn" aria-label="Cambiar tema">🌙</button>

    <div class="imageLogo sinFondo">
      <img src="../img/logo_sinfondo.png" alt="Logo de MercaAPP" class="imageLogo">
    </div>
    <div class="container" id="container">
      <div class="form-container sign-in-container">
        <form id="formLogin" method="post">
          <h1>Iniciar Sesión</h1>
          <div class="social-container">
            <a href="#" class="social"><i class="bi bi-facebook"></i></a>
            <a href="#" class="social"><i class="bi bi-google"></i></a>
            <a href="#" class="social"><i class="bi bi-linkedin"></i></a>
          </div>
          <span>Use su cuenta</span>
          <input type="email" name="email" placeholder="Escriba su correo" />
          <input type="password" name="password" placeholder="Contraseña" />
          <a href="#">¿Olvidaste tu contraseña?</a>
          <input type="submit" name="login" value="Iniciar Sesión" class="button-primary" />
        </form>
      </div>
    </div>
    <br>
    <a href="register.php">¿No tienes cuenta? Registrate aqui</a>
    </div>
</body>

</html>