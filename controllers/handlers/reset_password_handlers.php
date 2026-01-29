<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

$db = new Database();
$conn = $db->getConnection();
$userModel = new User($conn);

// Validar token usando el modelo
$user = $userModel->validateResetToken($email, $token);

if (!$user) {
    die("El enlace no es válido o ha caducado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST["password"])) {
        echo "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
        exit;
    }

    if (strlen($_POST["password"]) < 8) {
        echo "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
        exit;
    }

    // Actualizar contraseña usando el modelo
    $userModel->updatePassword($email, $_POST['password']);

    $message = "
    <div class='alert alert-success d-flex flex-column align-items-start'>
        <p class='mb-2'>Contraseña actualizada correctamente.</p>
        <a href='auth/login.php' class='btn btn-primary'>Inicia sesión</a>
    </div>";
}
