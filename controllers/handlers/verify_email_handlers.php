<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

$estado = "Procesando...";

try {
    // Conexión y modelo
    $db = new Database();
    $conn = $db->getConnection();
    $userModel = new User($conn);

    // Obtener usuario
    $u = $userModel->getByEmail($email);

    if (!$u) {
        $estado = "Enlace inválido.";
    } elseif ((int)$u['email_verificado'] === 1) {
        $estado = "Este correo ya está verificado.";
    } elseif ($u['verify_token'] && hash_equals($u['verify_token'], $token)) {

        // Verificar email usando el modelo
        $userModel->verifyEmail($email, $token);

        $estado = "Correo verificado correctamente. Ya puedes iniciar sesión.";

    } else {
        $estado = "Token incorrecto.";
    }

} catch (Exception $e) {
    error_log("Error en verificación: " . $e->getMessage());
    $estado = "Error en el servidor.";
}
