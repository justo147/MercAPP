<?php
/**
 * Script para solicitud de recuperación de contraseña.
 *
 * Este script procesa el formulario de "Olvidé mi contraseña":
 * - Verifica que se haya enviado un email por POST.
 * - Busca al usuario en la base de datos.
 * - Genera un token de recuperación y fecha de expiración.
 * - Guarda el token en la base de datos.
 * - Envía un correo al usuario con el enlace de restablecimiento.
 * - Muestra un mensaje de éxito o error.
 */

session_start();

// ===============================
// INCLUIR CONFIGURACIONES
// ===============================
require_once __DIR__ . '/../../config/mail_config.php';
require_once __DIR__ . '/../../config/db.php';

// ===============================
// PROCESAMIENTO DEL FORMULARIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener y limpiar email enviado
    $email = trim($_POST['email']);

    // Crear conexión a la base de datos
    $database = new Database();
    $pdo = $database->getConnection();

    // ===============================
    // BUSCAR USUARIO POR EMAIL
    // ===============================
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // ===============================
        // GENERAR TOKEN Y FECHA DE EXPIRACIÓN
        // ===============================
        $token = bin2hex(random_bytes(32)); // Token seguro de 64 caracteres hex
        $expires = date("Y-m-d H:i:s", time() + 3600); // Caduca en 1 hora

        // ===============================
        // GUARDAR TOKEN EN LA BASE DE DATOS
        // ===============================
        $stmt = $pdo->prepare("UPDATE usuario SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        // ===============================
        // PREPARAR Y ENVIAR CORREO
        // ===============================
        $resetLink = "http://localhost/MercApp/public/views/reset_password.php?token=$token";
        $htmlBody = "<p>Has solicitado restablecer tu contraseña.</p>
                     <p>Haz clic en el siguiente enlace para cambiarla:</p>
                     <p><a href='$resetLink'>$resetLink</a></p>
                     <p>Este enlace caduca en 1 hora.</p>";

        // Llamada a la función sendMail definida previamente
        sendMail($email, '', 'Recuperar contraseña - MercApp', $htmlBody);

        // Mensaje de éxito
        $mensaje = "Hemos enviado un enlace de recuperación a tu correo.";
    } else {
        // Mensaje si no se encuentra el email
        $mensaje = "No existe ninguna cuenta con ese correo.";
    }
}
