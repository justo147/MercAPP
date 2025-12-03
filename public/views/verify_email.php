<?php
// Cargar el handler correctamente: desde /public/views/ subir 2 niveles y entrar en /handlers/
require_once __DIR__ . '/../../controllers/handlers/verify_email_handlers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Verificación de correo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column align-items-center justify-content-center min-vh-100">
  <div class="card shadow p-4 rounded-3 bg-light" style="max-width: 480px; width: 100%;">
    <h1 class="text-center mb-3">Verificación de correo</h1>
    <div class="alert alert-info text-center"><?= htmlspecialchars($estado ?? 'Procesando verificación...') ?></div>
    <div class="text-center">
      <!-- login.php está en /public/views/auth/ -->
      <a href="auth/login.php" class="btn btn-primary">Ir a iniciar sesión</a>
    </div>
  </div>
</body>
</html>
