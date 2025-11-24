<?php
require __DIR__ . '/../../config/mail_config.php';
require __DIR__ . '/../../config/db.php'; // tu conexión a la BD

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $database = new Database();
    $pdo = $database->getConnection();

    // Buscar usuario
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generar token y fecha de expiración
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hora

        // Guardar en BD
        $stmt = $pdo->prepare("UPDATE usuario SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        // Enviar correo
        $resetLink = "http://localhost/MercApp/public/views/reset_password.php?token=$token";
        $htmlBody = "<p>Has solicitado restablecer tu contraseña.</p>
                     <p>Haz clic en el siguiente enlace para cambiarla:</p>
                     <p><a href='$resetLink'>$resetLink</a></p>
                     <p>Este enlace caduca en 1 hora.</p>";

        sendMail($email, '', 'Recuperar contraseña - MercApp', $htmlBody);

        echo "Hemos enviado un enlace de recuperación a tu correo.";
    } else {
        echo "No existe ninguna cuenta con ese correo.";
    }
}
