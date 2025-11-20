<?php
require_once '../../controllers/AuthController.php';

$error_message = "";

if (isset($_POST["login"])) {
    $auth = new AuthController();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($email, $password);
    
    // Si login retorna un string, es un mensaje de error
    if (is_string($result)) {
        $error_message = $result;
    }
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
  
  <link rel="stylesheet" href="../css/loginStyle.css">
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
          
          <?php if (!empty($error_message)): ?>
              <div class="error-message">
                  <?php echo htmlspecialchars($error_message); ?>
              </div>
          <?php endif; ?>

          <input type="email" name="email" placeholder="Escriba su correo" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
          <input type="password" name="password" placeholder="Contraseña" required />
          
          <a href="#">¿Olvidaste tu contraseña?</a>
          <input type="submit" name="login" value="Iniciar Sesión" class="button-primary" />
        </form>
      </div>
    </div>
    
    <br>
    <div style="text-align: center;">
        <a href="register.php">¿No tienes cuenta? Regístrate aquí</a>
    </div>

</body>
</html>