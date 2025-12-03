<?php
/**
 * Script de verificación de correo electrónico.
 *
 * Funcionalidad:
 * - Valida el token y el correo recibido por GET.
 * - Comprueba que el usuario exista y que su correo no haya sido verificado previamente.
 * - Si el token es correcto, actualiza la base de datos para marcar el email como verificado.
 * - Maneja errores de base de datos y estados de verificación.
 */

// Nota: No es necesaria sesión aquí a menos que quieras usar $_SESSION
// session_start();

// Obtener token y email desde la URL
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

// Variable para almacenar el estado de la verificación
$estado = "Procesando...";

try {
    // ===============================
    // CONEXIÓN A LA BASE DE DATOS
    // ===============================
    $bd = new PDO("mysql:host=localhost;dbname=mercapp;charset=utf8mb4", "root", "");
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ===============================
    // CONSULTAR USUARIO POR EMAIL
    // ===============================
    $sel = $bd->prepare("SELECT id, verify_token, email_verificado FROM usuario WHERE email = ?");
    $sel->execute([$email]);
    $u = $sel->fetch(PDO::FETCH_ASSOC);

    // ===============================
    // LÓGICA DE VERIFICACIÓN
    // ===============================
    if (!$u) {
        $estado = "Enlace inválido."; // Usuario no encontrado
    } elseif ((int)$u['email_verificado'] === 1) {
        $estado = "Este correo ya está verificado."; // Ya verificado
    } elseif ($u['verify_token'] && hash_equals($u['verify_token'], $token)) {
        // Token correcto: actualizar base de datos para marcar como verificado
        $upd = $bd->prepare("UPDATE usuario SET email_verificado = 1, verify_token = NULL WHERE id = ?");
        $upd->execute([$u['id']]);
        $estado = "Correo verificado correctamente. Ya puedes iniciar sesión.";
    } else {
        $estado = "Token incorrecto."; // Token inválido
    }
} catch (PDOException $e) {
    // Registrar error en log de PHP y actualizar estado
    error_log("Error en verificación: " . $e->getMessage());
    $estado = "Error en el servidor.";
}
