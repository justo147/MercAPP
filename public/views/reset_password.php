<?php
// RUTA CORREGIDA
require_once __DIR__ . '/../../config/db.php';

$token = $_GET['token'] ?? '';

$database = new Database();
$pdo = $database->getConnection();
$stmt = $pdo->prepare("SELECT id FROM usuario WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("El enlace no es válido o ha caducado.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column align-items-center justify-content-center min-vh-100 bg-light">
    <div class="card shadow p-4 rounded-3" style="max-width: 480px; width: 100%;">
        <h1 class="text-center mb-3">Restablecer contraseña</h1>
        <form method="post">
            <div class="mb-3">
                <label for="password" class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-success">Actualizar contraseña</button>
            </div>
        </form>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuario SET contraseña_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$newPass, $user['id']]);
        echo "Contraseña actualizada correctamente. <a href='auth/login.php'>Inicia sesión</a>";
    }
    ?>
</body>
</html>