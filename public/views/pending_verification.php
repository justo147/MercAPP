<?php
session_start();
$email = $_SESSION['pending_email'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Confirmación pendiente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column align-items-center justify-content-center min-vh-100 bg-light">
  <div class="card shadow p-4 rounded-3" style="max-width: 480px; width: 100%;">
    <h1 class="text-center mb-3">Confirma tu correo</h1>
    <div class="alert alert-info text-center">
      <?php if ($email): ?>
        Hemos enviado un enlace de confirmación a <strong><?= htmlspecialchars($email) ?></strong>.
      <?php else: ?>
        Hemos enviado un enlace de confirmación a tu correo electrónico.
      <?php endif; ?>
      <br>Revisa tu bandeja de entrada y sigue las instrucciones.
    </div>
    <p class="text-center text-muted">
      Si no ves el correo, revisa la carpeta de spam o espera unos minutos.
    </p>
    <div class="text-center mt-3">
      <a href="login.php" class="btn btn-primary">Ir a iniciar sesión</a>
    </div>
  </div>
</body>
</html>
