<?php
session_start();
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$estado = "Procesando...";

try {
  $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");
  $sel = $bd->prepare("SELECT id, verify_token, email_verificado FROM usuario WHERE email = ?");
  $sel->execute([$email]);
  $u = $sel->fetch(PDO::FETCH_ASSOC);

  if (!$u) {
    $estado = "Enlace inválido.";
  } elseif ((int)$u['email_verificado'] === 1) {
    $estado = "Este correo ya está verificado.";
  } elseif ($u['verify_token'] && hash_equals($u['verify_token'], $token)) {
    $upd = $bd->prepare("UPDATE usuario SET email_verificado = 1, verify_token = NULL WHERE id = ?");
    $upd->execute([$u['id']]);
    $estado = "Correo verificado correctamente. Ya puedes iniciar sesión.";
  } else {
    $estado = "Token incorrecto.";
  }
} catch (PDOException $e) {
  $estado = "Error en el servidor.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><title>Verificación de correo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column align-items-center justify-content-center min-vh-100">
  <div class="card shadow p-4 rounded-3 bg-light" style="max-width: 480px; width: 100%;">
    <h1 class="text-center mb-3">Verificación de correo</h1>
    <div class="alert alert-info text-center"><?= htmlspecialchars($estado) ?></div>
    <div class="text-center">
      <a href="login.php" class="btn btn-primary">Ir a iniciar sesión</a>
    </div>
  </div>
</body>
</html>
