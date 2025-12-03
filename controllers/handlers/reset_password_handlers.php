<?php
/**
 * Script de restablecimiento de contraseña.
 *
 * Funcionalidad:
 * - Valida el token de recuperación recibido por GET.
 * - Comprueba que el token exista y no haya expirado.
 * - Permite al usuario establecer una nueva contraseña.
 * - Actualiza la contraseña en la base de datos y elimina el token.
 */

// ===============================
// INCLUIR CONFIGURACIÓN DE BASE DE DATOS
// ===============================
require_once __DIR__ . '/../../config/db.php';

// Obtener token enviado por URL
$token = $_GET['token'] ?? '';

// ===============================
// CONEXIÓN A LA BASE DE DATOS
// ===============================
$database = new Database();
$pdo = $database->getConnection();

// ===============================
// VERIFICAR TOKEN Y FECHA DE EXPIRACIÓN
// ===============================
$stmt = $pdo->prepare("SELECT id FROM usuario WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

// Si el token no es válido o ha expirado, terminar ejecución
if (!$user) {
    die("El enlace no es válido o ha caducado.");
}

// ===============================
// PROCESAR FORMULARIO DE NUEVA CONTRASEÑA
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validaciones
    if (empty($_POST["password"])) {
        echo "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
        exit;
    } elseif (strlen($_POST["password"]) < 8) {
        echo "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
        exit;
    } else {
        // ===============================
        // ACTUALIZAR CONTRASEÑA EN LA BASE DE DATOS
        // ===============================
        $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "UPDATE usuario 
             SET contraseña_hash = ?, reset_token = NULL, reset_expires = NULL 
             WHERE id = ?"
        );
        $stmt->execute([$newPass, $user['id']]);
        $message = "
<div class='alert alert-success d-flex flex-column align-items-start'>
  <p class='mb-2'>Contraseña actualizada correctamente.</p>
  <a href='auth/login.php' class='btn btn-primary'>Inicia sesión</a>
</div>";
    }
}
