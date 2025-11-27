<?php
require_once '../../controllers/AuthController.php';



$error_message = "";
//Cambiamos la condicion porque al hacer el .submit() desde js no se ejecuta el input del formulario
if (!empty($_POST['email']) && !empty($_POST['password'])) {
  $auth = new AuthController();
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  $result = $auth->login($email, $password);

  // Si login retorna un string, es un mensaje de error
  if (is_string($result)) {
    $error_message = $result;
  }
}
//comprobamos que la session no estaba iniciada
if(isset($_SESSION["user_id"])){
  header("location:home.php");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión - MercApp</title>
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <!--links de bootstrap-->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

  <link rel="stylesheet" href="../css/loginStyle.css">
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <script src="../js/login.js" defer></script>
  <script src="../js/theme.js" defer></script>
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
      <img src="../img/logo_sinfondo.png" alt="Logo de MercaAPP" class="imageLogo">
    </div>
  </header>

  <div class="container d-flex justify-content-center align-items-center sinFondo" id="container">
    <form id="formLogin" method="post" class="form">
      <h1>Iniciar Sesión</h1>
  <!--bootstrap para los iconos y redondearlo-->
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
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <input type="email" id="email" name="email" class="form-control border border-primary rounded" placeholder="Escriba su correo" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
      <input type="password" id="password" name="password" class="form-control border border-primary rounded" placeholder="Contraseña" required />

      <a href="forgotpass.php">¿Olvidaste tu contraseña?</a>
      <input type="submit" name="login" value="Iniciar Sesión" class="button-primary" />
    </form>
  </div>

  <br>
  <div style="text-align: center;" class="sinFondo">
    <a href="register.php">¿No tienes cuenta? Regístrate aquí</a>
  </div>

</body>

</html>