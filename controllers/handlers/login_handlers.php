<?php
/**
 * Handler de login.
 *
 * Este script procesa el formulario de inicio de sesión.
 * - Verifica que se hayan enviado email y contraseña.
 * - Llama al AuthController para autenticar al usuario.
 * - Maneja errores de login y redirige al home si la sesión ya está iniciada.
 */

// ===============================
// INICIO DE SESIÓN
// ===============================
// Siempre iniciar la sesión antes de trabajar con $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===============================
// INCLUIR CONTROLADOR DE AUTENTICACIÓN
// ===============================
// Ruta corregida: desde /handlers/ basta con subir un nivel para llegar a /controllers/
require_once __DIR__ . '/../AuthController.php';

// Variable para almacenar mensajes de error
$error_message = "";

// ===============================
// PROCESAR FORMULARIO DE LOGIN
// ===============================
if (!empty($_POST['email']) && !empty($_POST['password'])) {
    $auth = new AuthController();

    // Recoger datos del formulario
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Llamar al método login del controlador
    $result = $auth->login($email, $password);

    // Si login retorna un string, significa que hubo un error
    if (is_string($result)) {
        $error_message = $result;
    }
}

// ===============================
// REDIRECCIÓN SI YA HAY SESIÓN
// ===============================
if (isset($_SESSION["user_id"])) {
    // Ruta relativa correcta desde /handlers/ hasta /public/views/home.php
    header("Location: ../home.php");
    exit;
}
