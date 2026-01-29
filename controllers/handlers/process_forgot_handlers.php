<?php
session_start();

require_once __DIR__ . '/../../config/mail_config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);

    // Conexión y modelo
    $db = new Database();
    $conn = $db->getConnection();
    $userModel = new User($conn);

    // Buscar usuario
    $user = $userModel->getByEmail($email);

    if ($user) {

        // Generar token y expiración
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600);

        // Guardar token usando el modelo
        $userModel->setResetToken($email, $token, $expires);

        // Enlace de recuperación
        $resetLink = "http://localhost/MercApp/public/views/reset_password.php?token=$token&email=" . urlencode($email);

        $htmlBody = "
            <p>Has solicitado restablecer tu contraseña.</p>
            <p>Haz clic en el siguiente enlace para cambiarla:</p>
            <p><a href='$resetLink'>$resetLink</a></p>
            <p>Este enlace caduca en 1 hora.</p>
        ";

        sendMail($email, '', 'Recuperar contraseña - MercApp', $htmlBody);

        $mensaje = "Hemos enviado un enlace de recuperación a tu correo.";

    } else {
        $mensaje = "No existe ninguna cuenta con ese correo.";
    }
}
