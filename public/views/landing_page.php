<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MercApp - Compra y Venta</title>
  
  <!-- Favicon de la aplicación -->
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  
  <!-- CSS -->
  <link rel="stylesheet" href="../css/reset.css">        <!-- Reset minimalista -->
  <link rel="stylesheet" href="../css/style-guide.css">  <!-- Variables y estilos globales -->
  <link rel="stylesheet" href="../css/style.css">        <!-- Estilos específicos de la landing -->
  
  <!-- JS controlador de landing (manejo de botones de login/registro) -->
  <script src="../../controllers/landing_controllers.js"></script>
</head>

<body class="landing-page">
  <!-- Contenido principal de bienvenida -->
  <div class="contenidoLandingPage">
    <h1>Bienvenido a MercApp</h1>
    <h2>Compra y venta de segunda mano</h2>
  </div>

  <!-- Botones de navegación a login y registro -->
  <div class="botonesLandingPage">
    <button id="btn-login" class="button-primary">Iniciar sesión</button>
    <button id="btn-register" class="button-primary">Regístrate</button>
  </div>

  <!--
    NOTA: La navegación de los botones se gestiona mediante 
    landing_controllers.js, que redirige a login.php o register.php.
  -->
</body>
</html>
