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
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/registro.js" defer></script>
  <script src="../js/theme.js" defer></script>
</head>

<body>
  <button id="themeToggle" class="toggle-btn" aria-label="Cambiar tema">🌙</button>
  <div class="imageLogo sinFondo">
    <img src="../img/logo_sinfondo.png" alt="Logo de MercaAPP">
  </div>

  <div class="form container">
    <form id="formRegistro" method="post">
      <h1>Registrar Cuenta</h1>
      Nick: <input type="text" name="name" /><br />
      Correo electronico: <input type="email" name="email" /><br />
      Contraseña: <input type="password" name="password" /><br />
      Confirmar contraseña: <input type="password" name="confirmPass" /><br /><br />
      <input type="submit" name="register" value="Registrarse" class="button-primary" />
    </form>
  </div>

  <br />

  <a href="login.php">¿Ya estás registrado? Inicia sesión aqui</a>
</body>

</html>